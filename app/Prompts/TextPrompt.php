<?php

namespace App\Prompts;

use Laravel\Prompts\TextPrompt as PromptsTextPrompt;

class TextPrompt extends PromptsTextPrompt
{
    use Concerns\AllowWindowsTerminal;
    use Concerns\TypedValue;
}
