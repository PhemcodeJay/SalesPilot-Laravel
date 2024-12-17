<?php

namespace App\Http\Controllers;

use OpenAI\Client as OpenAIClient;
use Illuminate\Http\Request;

class OpenAIController extends Controller
{
    protected $openAI;

    public function __construct(OpenAIClient $openAI)
    {
        $this->openAI = $openAI;
    }

    public function generateText(Request $request)
    {
        $response = $this->openAI->completions()->create([
            'model' => 'text-davinci-003',
            'prompt' => $request->input('prompt'),
            'max_tokens' => 100,
        ]);

        return response()->json($response);
    }
}
