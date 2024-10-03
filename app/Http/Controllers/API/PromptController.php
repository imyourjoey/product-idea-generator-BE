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
            "useBrandProfile" => "required|boolean",
            "selectedProductCategory" => "required|string",
            "userPrompt" => "required|string",
            "brandProducts" => "required",
        ]);

        // Get parameters from the request
        $useBrandProfile = $request->input("useBrandProfile");
        $selectedProductCategory = $request->input("selectedProductCategory");
        $userPrompt = $request->input("userPrompt");
        $brandProducts = $request->input("brandProducts");

        // Craft the final prompt for full-length response
        $fullLengthPrompt = $this->craftPrompt(
            $useBrandProfile,
            $selectedProductCategory,
            $userPrompt,
            $brandProducts
        );

        // Prepare the request body for Groq AI
        $groqApiKey = env("GROQ_API_KEY");
        $groqRequestBody = [
            "messages" => [
                [
                    "role" => "user",
                    "content" => $fullLengthPrompt,
                ],
            ],
            "model" => "llama3-8b-8192",
        ];

        try {
            // First request: Send full-length response request
            $fullLengthResponse = Http::withHeaders([
                "Authorization" => "Bearer {$groqApiKey}",
                "Content-Type" => "application/json",
            ])->post(
                "https://api.groq.com/openai/v1/chat/completions",
                $groqRequestBody
            );

            // Check if the first response was successful
            if ($fullLengthResponse->successful()) {
                $fullContent = $fullLengthResponse->json()["choices"][0][
                    "message"
                ]["content"];

                // Second request: Ask Groq to summarize the full response into one concise sentence
                $shortRequestBody = [
                    "messages" => [
                        [
                            "role" => "user",
                            "content" => "{$fullContent}. Based on the previous content, describe the new proposed product's appearance, name, and key characteristics in a concise 6-7 word sentence, excluding any filler words.",
                        ],
                    ],
                    "model" => "llama3-8b-8192",
                ];

                $shortResponse = Http::withHeaders([
                    "Authorization" => "Bearer {$groqApiKey}",
                    "Content-Type" => "application/json",
                ])->post(
                    "https://api.groq.com/openai/v1/chat/completions",
                    $shortRequestBody
                );

                // Check for success on the second request
                if ($shortResponse->successful()) {
                    $shortContent = $shortResponse->json()["choices"][0][
                        "message"
                    ]["content"];

                    // Third request: Ask Groq for the target market
                    $targetMarketRequestBody = [
                        "messages" => [
                            [
                                "role" => "user",
                                "content" => "{$fullContent}. Based on the full proposal, describe the ideal target market for this product in a detailed but concise manner. Just a 1-5 word phrase (e.g., tech-savvy people, elderly). don't give filler text, don't need to be a sentence",
                            ],
                        ],
                        "model" => "llama3-8b-8192",
                    ];

                    $targetMarketResponse = Http::withHeaders([
                        "Authorization" => "Bearer {$groqApiKey}",
                        "Content-Type" => "application/json",
                    ])->post(
                        "https://api.groq.com/openai/v1/chat/completions",
                        $targetMarketRequestBody
                    );

                    // Check for success on the third request
                    if ($targetMarketResponse->successful()) {
                        $targetMarketContent = $targetMarketResponse->json()[
                            "choices"
                        ][0]["message"]["content"];

                        // Fourth request: Ask Groq for a product name
                        $productNameRequestBody = [
                            "messages" => [
                                [
                                    "role" => "user",
                                    "content" => "{$fullContent}. based on the previous content, find the product name. Return only the name.",
                                ],
                            ],
                            "model" => "llama3-8b-8192",
                        ];

                        $productNameResponse = Http::withHeaders([
                            "Authorization" => "Bearer {$groqApiKey}",
                            "Content-Type" => "application/json",
                        ])->post(
                            "https://api.groq.com/openai/v1/chat/completions",
                            $productNameRequestBody
                        );

                        // Check for success on the fourth request
                        if ($productNameResponse->successful()) {
                            $productNameContent = $productNameResponse->json()[
                                "choices"
                            ][0]["message"]["content"];

                            // FIFTH request: Ask Groq for a USP
                            $UspRequestBody = [
                                "messages" => [
                                    [
                                        "role" => "user",
                                        "content" => "{$fullContent}. Provide unique selling point of the product, no filler sentences, straight to the point, do not prepend Unique Selling Point: in front", 
                                    ],
                                ],
                                "model" => "llama3-8b-8192",
                            ];

                            $UspResponse = Http::withHeaders([
                                "Authorization" => "Bearer {$groqApiKey}",
                                "Content-Type" => "application/json",
                            ])->post(
                                "https://api.groq.com/openai/v1/chat/completions",
                                $UspRequestBody
                            );

                            // Check for success on the fourth request
                            if ($UspResponse->successful()) {
                                $UspContent = $UspResponse->json()[
                                    "choices"
                                ][0]["message"]["content"];

                                // Sixth request: Ask Groq for a estimated cost
                                $EstimatedCostRequestBody = [
                                    "messages" => [
                                        [
                                            "role" => "user",
                                            "content" => "{$fullContent}. Provide a estimated manufacturing cost per unit in USD. just give the number, with 2 decimal places, dont give currency symbol",
                                        ],
                                    ],
                                    "model" => "llama3-8b-8192",
                                ];

                                $EstimatedCostResponse = Http::withHeaders([
                                    "Authorization" => "Bearer {$groqApiKey}",
                                    "Content-Type" => "application/json",
                                ])->post(
                                    "https://api.groq.com/openai/v1/chat/completions",
                                    $EstimatedCostRequestBody
                                );

                                // Check for success on the sixth request
                                if ($EstimatedCostResponse->successful()) {
                                    $EstimatedCostContent = $EstimatedCostResponse->json()[
                                        "choices"
                                    ][0]["message"]["content"];

                                    // Seventh request: Ask Groq for a selling prince
                                    $estimatedSellingPriceRequestBody = [
                                        "messages" => [
                                            [
                                                "role" => "user",
                                                "content" => "this is the estimated manufacturing cost per unit {$EstimatedCostContent}. Provide a estimated selling price per unit in USD. just give the number, with 2 decimal places. make sure the selling price is higher than the manufacturing cost price, very very important, for profit",
                                            ],
                                        ],
                                        "model" => "llama3-8b-8192",
                                    ];

                                    $estimatedSellingPriceResponse = Http::withHeaders(
                                        [
                                            "Authorization" => "Bearer {$groqApiKey}",
                                            "Content-Type" =>
                                                "application/json",
                                        ]
                                    )->post(
                                        "https://api.groq.com/openai/v1/chat/completions",
                                        $EstimatedCostRequestBody
                                    );

                                    // Check for success on the fourth request
                                    if (
                                        $estimatedSellingPriceResponse->successful()
                                    ) {
                                        $estimatedSellingPriceContent = $estimatedSellingPriceResponse->json()[
                                            "choices"
                                        ][0]["message"]["content"];

                                        // EIGHTH request: Ask Groq for a units sold per month
                                        $unitsSoldPerMonthRequestBody = [
                                            "messages" => [
                                                [
                                                    "role" => "user",
                                                    "content" => "{$fullContent}. this is the estimated cost per unit {$EstimatedCostContent}. this is the estimated selling price {$estimatedSellingPriceContent}. Provide a estimated units sold per month, just give me the number in your response, don't give anything else, don't need comma separator",
                                                ],
                                            ],
                                            "model" => "llama3-8b-8192",
                                        ];

                                        $unitsSoldPerMonthResponse = Http::withHeaders(
                                            [
                                                "Authorization" => "Bearer {$groqApiKey}",
                                                "Content-Type" =>
                                                    "application/json",
                                            ]
                                        )->post(
                                            "https://api.groq.com/openai/v1/chat/completions",
                                            $unitsSoldPerMonthRequestBody
                                        );

                                        // Check for success on the fourth request
                                        if (
                                            $unitsSoldPerMonthResponse->successful()
                                        ) {
                                            $unitsSoldPerMonthContent = $unitsSoldPerMonthResponse->json()[
                                                "choices"
                                            ][0]["message"]["content"];

                                            // Ninth request: Ask Groq for a description
                                            $descriptionRequestBody = [
                                                "messages" => [
                                                    [
                                                        "role" => "user",
                                                        "content" => "{$fullContent}. based on the content before this sentence write a description about the proposed product, about 100 words, dont add formatting write in a paragraph, keep as much information as possible from the previous content",
                                                    ],
                                                ],
                                                "model" => "llama3-8b-8192",
                                            ];

                                            $descriptionResponse = Http::withHeaders(
                                                [
                                                    "Authorization" => "Bearer {$groqApiKey}",
                                                    "Content-Type" =>
                                                        "application/json",
                                                ]
                                            )->post(
                                                "https://api.groq.com/openai/v1/chat/completions",
                                                $descriptionRequestBody
                                            );

                                            // Check for success on the fourth request
                                            if (
                                                $descriptionResponse->successful()
                                            ) {
                                                $descriptionContent = $descriptionResponse->json()[
                                                    "choices"
                                                ][0]["message"]["content"];

                                                // Return all responses (full response, short summary, target market, and product name)
                                                return response()->json(
                                                    [
                                                        "fullResponse" => $fullContent,
                                                        "shortResponse" => $shortContent,
                                                        "targetMarket" => $targetMarketContent,
                                                        "productName" => $productNameContent,
                                                        "uniqueSellingPoint" => $UspContent,
                                                        "estimatedCost" => $EstimatedCostContent,
                                                        "estimatedSellingPrice" => $estimatedSellingPriceContent,
                                                        "estimatedUnitsSoldPerMonth" => $unitsSoldPerMonthContent,
                                                        "description" => $descriptionContent,
                                                    ],
                                                    200
                                                );
                                            } else {
                                                return response()->json(
                                                    [
                                                        "message" =>
                                                            "Error processing the USP",
                                                    ],
                                                    $descriptionResponse->status()
                                                );
                                            }
                                        } else {
                                            return response()->json(
                                                [
                                                    "message" =>
                                                        "Error processing the USP",
                                                ],
                                                $unitsSoldPerMonthResponse->status()
                                            );
                                        }
                                    } else {
                                        return response()->json(
                                            [
                                                "message" =>
                                                    "Error processing the USP",
                                            ],
                                            $estimatedSellingPriceResponse->status()
                                        );
                                    }
                                } else {
                                    return response()->json(
                                        [
                                            "message" =>
                                                "Error processing the USP",
                                        ],
                                        $EstimatedCostResponse->status()
                                    );
                                }
                            } else {
                                return response()->json(
                                    ["message" => "Error processing the USP"],
                                    $UspResponse->status()
                                );
                            }
                        } else {
                            return response()->json(
                                [
                                    "message" =>
                                        "Error processing the product name",
                                ],
                                $productNameResponse->status()
                            );
                        }
                    } else {
                        return response()->json(
                            ["message" => "Error processing the target market"],
                            $targetMarketResponse->status()
                        );
                    }
                } else {
                    return response()->json(
                        ["message" => "Error processing the short summary"],
                        $shortResponse->status()
                    );
                }
            } else {
                return response()->json(
                    ["message" => "Error processing the full response"],
                    $fullLengthResponse->status()
                );
            }
        } catch (\Exception $e) {
            return response()->json(
                [
                    "message" =>
                        "Error processing the prompt: " . $e->getMessage(),
                ],
                500
            );
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
            ? "Products I currently have in my brand: " .
                json_encode($brandProducts) .
                ".\n"
            : "Please give me innovative products based on my prompt";

        return $header .
            $brandProfile .
            $categoryInfo .
            $productsList .
            "User prompt: {$userPrompt}";
    }
}
