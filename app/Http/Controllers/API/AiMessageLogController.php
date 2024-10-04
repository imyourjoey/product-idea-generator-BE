<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AiMessageLog;
use Illuminate\Http\Request;

class AiMessageLogController extends Controller
{
    public function index($generatedProductIdeaId)
    {
        // Get all AI message logs for the specified generated_product_idea_id
        $logs = AiMessageLog::with('generatedProductIdea')
            ->where('generated_product_idea_id', $generatedProductIdeaId)
            ->get()
            ->map(function ($log) {
                // Return only the necessary fields
                return [
                    'id' => $log->id,
                    'question' => $log->question,
                    'answer' => $log->answer,
                    'generated_product_idea_id' => $log->generated_product_idea_id,
                    'created_at' => $log->created_at,

                ];
            });

        // Return the logs as a JSON response
        return response()->json($logs);
    }

    public function store(Request $request)
    {
        // Validate request
        $request->validate([
            'generated_product_idea_id' => 'required|exists:generated_product_ideas,id',
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        // Create a new AI message log
        $log = AiMessageLog::create($request->all());

        return response()->json($log, 201);
    }

    public function show($id)
    {
        // Get a specific AI message log
        $log = AiMessageLog::with('generatedProductIdea')->findOrFail($id);

        return response()->json($log);
    }

    public function update(Request $request, $id)
    {
        // Validate request
        $request->validate([
            'question' => 'sometimes|required|string',
            'answer' => 'sometimes|required|string',
        ]);

        // Update the AI message log
        $log = AiMessageLog::findOrFail($id);
        $log->update($request->all());

        return response()->json($log);
    }

    public function destroy($id)
    {
        // Delete the AI message log
        $log = AiMessageLog::findOrFail($id);
        $log->delete();

        return response()->json(null, 204);
    }
}
