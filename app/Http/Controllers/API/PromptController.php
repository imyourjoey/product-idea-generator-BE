<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
        $fullLengthPrompt = $this->craftPrompt($useBrandProfile, $selectedProductCategory, $userPrompt, $brandProducts);

        // Prepare the request body for Groq AI
        $groqApiKey = env('GROQ_API_KEY');
        $groqRequestBody = [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $fullLengthPrompt
                ]
            ],
            'model' => 'llama3-8b-8192'
        ];

        try {
            // First request: Send full-length response request
            $fullLengthResponse = Http::withHeaders([
                'Authorization' => "Bearer {$groqApiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', $groqRequestBody);

            // Check if the first response was successful
            if ($fullLengthResponse->successful()) {
                $fullContent = $fullLengthResponse->json()['choices'][0]['message']['content'];

                // Second request: Ask Groq to summarize the full response into one concise sentence
                $shortRequestBody = [
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => "{$fullContent}.Based on the previous content, describe the new proposed product's appearance, name, and key characteristics in a concise 6-7 word sentence, excluding any filler words."
                        ]
                    ],
                    'model' => 'llama3-8b-8192'
                ];

                $shortResponse = Http::withHeaders([
                    'Authorization' => "Bearer {$groqApiKey}",
                    'Content-Type' => 'application/json',
                ])->post('https://api.groq.com/openai/v1/chat/completions', $shortRequestBody);

                // Check for success on the second request
                if ($shortResponse->successful()) {
                    $shortContent = $shortResponse->json()['choices'][0]['message']['content'];

                    // Return both responses
                    return response()->json([
                        'fullResponse' => $fullContent,
                        'shortResponse' => $shortContent
                    ], 200);
                } else {
                    return response()->json(['message' => 'Error processing the short summary'], $shortResponse->status());
                }
            } else {
                return response()->json(['message' => 'Error processing the full response'], $fullLengthResponse->status());
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error processing the prompt: ' . $e->getMessage()], 500);
        }
    }

    // Method to craft the final full-length prompt
    private function craftPrompt($useBrandProfile, $selectedProductCategory, $userPrompt, $brandProducts)
    {
        $header = "Please provide the prompt in plain HTML format without the use of ```html. The length should be under 200 words. Use only simple HTML tags such as <b>, <blockquote>, <br>, and <li>. Whenever you use a <br> tag, insert two <br> tags. Based on the brand profile, and product list, Please suggest an innovative product, your response should only focus on the product's key features, market demand, unique selling point, dont write about my company, write about your proposal";
        $brandProfile = $useBrandProfile ? "Using brand profile.\n" : "Not using brand profile.\n";
        $categoryInfo = "Selected category: {$selectedProductCategory}.\n";
        $productsList = $useBrandProfile ? "Products I currently have in my brand: " . json_encode($brandProducts) . ".\n" : 'Please give me innovative products based on my prompt'; 

        return $header . $brandProfile . $categoryInfo . $productsList . "User prompt: {$userPrompt}";
    }
}
