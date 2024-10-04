<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AiMessageLog;
use App\Models\GeneratedProductIdea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class PromptController extends Controller
{
    public function handlePrompt(Request $request)
    {
        // Validate the request
        $request->validate([
            'useBrandProfile' => 'required|boolean',
            'selectedProductCategory' => 'required|string',
            'userPrompt' => 'required|string',
            'brandProducts' => 'required',
        ]);

        // Get parameters from the request
        $useBrandProfile = $request->input('useBrandProfile');
        $selectedProductCategory = $request->input('selectedProductCategory');
        $userPrompt = $request->input('userPrompt');
        $brandProducts = $request->input('brandProducts');

        // Craft the final prompt for full-length response
        $fullLengthPrompt = $this->craftPrompt(
            $useBrandProfile,
            $selectedProductCategory,
            $userPrompt,
            $brandProducts
        );

        // Prepare the request body for Groq AI
        $groqApiKey = env('GROQ_API_KEY');
        $model = 'llama3-8b-8192';

        try {
            // First request: Send full-length response request
            $fullContent = $this->makeGroqRequest($fullLengthPrompt, $groqApiKey, $model);

            // If first request was successful, proceed with further requests
            if ($fullContent) {
                $shortContent = $this->makeGroqRequest("{$fullContent}. Based on the previous content, describe the new proposed product's appearance, name, and key characteristics in a concise 6-7 word sentence, excluding any filler words.", $groqApiKey, $model);
                $targetMarketContent = $this->makeGroqRequest("{$fullContent}. Based on the full proposal, describe the ideal target market for this product in a detailed but concise manner. Just a 1-5 word phrase (e.g., tech-savvy people, elderly). don't give filler text, don't need to be a sentence", $groqApiKey, $model);
                $productNameContent = $this->makeGroqRequest("{$fullContent}. based on the previous content, find the product name. Return only the name.", $groqApiKey, $model);
                $UspContent = $this->makeGroqRequest("{$fullContent}. Provide unique selling point of the product, no filler sentences, straight to the point, do not prepend Unique Selling Point: in front", $groqApiKey, $model);
                $EstimatedCostContent = $this->makeGroqRequest("{$fullContent}. Provide a estimated manufacturing cost per unit in USD. just give the number, with 2 decimal places, dont give currency symbol", $groqApiKey, $model);
                $estimatedSellingPriceContent = $this->makeGroqRequest("this is the estimated manufacturing cost per unit {$EstimatedCostContent}. Provide a estimated selling price per unit in USD. just give the number, with 2 decimal places. make sure the selling price is higher than the manufacturing cost price, very very important, for profit", $groqApiKey, $model);
                $unitsSoldPerMonthContent = $this->makeGroqRequest("{$fullContent}. this is the estimated cost per unit {$EstimatedCostContent}. this is the estimated selling price {$estimatedSellingPriceContent}. Provide a estimated units sold per month, just give me the number in your response, don't give anything else, don't need comma separator", $groqApiKey, $model);
                $descriptionContent = $this->makeGroqRequest("{$fullContent}. based on the content before this sentence write a description about the proposed product, about 100 words, dont add formatting write in a paragraph, keep as much information as possible from the previous content", $groqApiKey, $model);

                // Create a new GeneratedProductIdea entry
                $productIdea = GeneratedProductIdea::create([
                    // 'full_response' => $fullContent,
                    // 'short_response' => $shortContent,
                    'category' => $request->input('selectedProductCategory'),
                    'target_market' => $targetMarketContent,
                    'product_name' => $productNameContent,
                    'unique_selling_point' => $UspContent,
                    'estimated_cost' => $EstimatedCostContent,
                    'estimated_selling_price' => $estimatedSellingPriceContent,
                    'estimated_units_sold_per_month' => $unitsSoldPerMonthContent,
                    'description' => $descriptionContent,
                    'feasibility_score' => rand(6, 10),
                    'user_id' => Auth::id(),
                    'brand_id' => $request->input('brandProducts.brand.brand_id'),
                ]);

                // Return all responses
                return response()->json([
                    'fullResponse' => $fullContent,
                    'shortResponse' => $shortContent,
                    'targetMarket' => $targetMarketContent,
                    'productName' => $productNameContent,
                    'uniqueSellingPoint' => $UspContent,
                    'estimatedCost' => $EstimatedCostContent,
                    'estimatedSellingPrice' => $estimatedSellingPriceContent,
                    'estimatedUnitsSoldPerMonth' => $unitsSoldPerMonthContent,
                    'description' => $descriptionContent,
                ], 200);
            } else {
                return response()->json(['message' => 'Error processing the full response'], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error processing the prompt: '.$e->getMessage(),
            ], 500);
        }
    }

    private function makeGroqRequest($promptContent, $groqApiKey, $model)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$groqApiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $promptContent,
                ],
            ],
            'model' => $model,
        ]);

        // Check if the request was successful and return the content
        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'];
        } else {
            // Log the error for debugging purposes (optional)
            Log::error('Groq API Error: '.$response->status().' - '.$response->body());

            return null; // Return null to signal a failure
        }
    }

    // Method to craft the final full-length prompt
    private function craftPrompt(
        $useBrandProfile,
        $selectedProductCategory,
        $userPrompt,
        $brandProducts
    ) {
        $header =
            "Please provide the prompt in plain HTML format without the use of ```html. The length should be under 200 words. Use only simple HTML tags such as <b>, <blockquote>, <br>, and <li>. Whenever you use a <br> tag, insert two <br> tags. Based on the brand profile, and product list, Please suggest an innovative product, your response should only focus on the product's key features, market demand, unique selling point, don't write about my company, write about your proposal.";
        $brandProfile = $useBrandProfile
            ? "Using brand profile.\n"
            : "Not using brand profile.\n";
        $categoryInfo = "Selected category: {$selectedProductCategory}.\n";
        $productsList = $useBrandProfile
            ? 'Products I currently have in my brand: '.
                json_encode($brandProducts).
                ".\n"
            : 'Please give me innovative products based on my prompt';

        return $header.
            $brandProfile.
            $categoryInfo.
            $productsList.
            "User prompt: {$userPrompt}";
    }

    public function handleAskAi(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'userInput' => 'required',
            'ideaData' => 'required',
        ]);

        // Gather inputs from the request
        $uniqueSellingPoint = 'this is unique selling point of the product:'.$request->input('ideaData.unique_selling_point');
        $feasibilityScore = 'this is feasibility score of the product, out of 10:'.$request->input('ideaData.feasibility_score');
        $estimatedUnitsSold = 'this is the estimated units sold per month:'.$request->input('ideaData.estimated_units_sold_per_month');
        $estimatedSellingPrice = 'this is the estimated selling price of the product:'.$request->input('ideaData.estimated_selling_price');
        $description = 'this is description of the product, you can answer alot of question from this description:'.$request->input('ideaData.description');
        $estimatedCost = 'this is the estimated cost of the product:'.$request->input('ideaData.estimated_cost');
        $category = 'this is the product category:'.$request->input('ideaData.category');
        $brandName = 'this is the brand name:'.$request->input('ideaData.brand_name');
        $productName = 'this is the product name:'.$request->input('ideaData.product_name');
        $targetMarket = 'this is the target market:'.$request->input('ideaData.target_market');
        $userInput = $request->input('userInput');

        // Construct the user prompt
        $userPrompt = $uniqueSellingPoint.
            $feasibilityScore.
            $estimatedUnitsSold.
            $estimatedSellingPrice.
            $description.
            $estimatedCost.
            $category.
            $brandName.
            $productName.
            $targetMarket.
            ' based on the content before this sentence, answer the following question in a clear and concise manner, not more than 100 words write in a paragraph, even if it not a question, treat it like a question, and dont add filler words/sentences, straight to the point. the next sentence is the question/statement: '.
            $userInput;

        // Prepare the request body for Groq AI
        $groqApiKey = env('GROQ_API_KEY');
        $model = 'llama3-8b-8192';

        try {
            // Send the user prompt to Groq AI
            $responseContent = $this->makeGroqRequest($userPrompt, $groqApiKey, $model);

            if ($responseContent) {
                $logData = [
                    'generated_product_idea_id' => $request->input('ideaData.generated_product_idea_id'),
                    'question' => $userInput,
                    'answer' => $responseContent,
                ];

                AiMessageLog::create($logData);

                // Return the AI response
                return response()->json([
                    'response' => $responseContent,
                ], 200);
            } else {
                return response()->json(['message' => 'Error processing the AI request'], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error handling AI request: '.$e->getMessage(),
            ], 500);
        }
    }
}
