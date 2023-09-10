<?php

namespace App\Prompts;

use Laravel\Prompts\PasswordPrompt as PromptsPasswordPrompt;

class PasswordPrompt extends PromptsPasswordPrompt
{
    use Concerns\AllowWindowsTerminal;
    use Concerns\TypedValue;
}
