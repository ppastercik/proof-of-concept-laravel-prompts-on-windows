<?php

namespace App\Prompts;

use Closure;
use Laravel\Prompts\ConfirmPrompt as PromptsConfirmPrompt;

class ConfirmPrompt extends PromptsConfirmPrompt
{
    use Concerns\AllowWindowsTerminal;

    /**
     * Overrided to add ability to use Enter on Windows system.
     *
     * @inheritDoc
     */
    public function __construct(
        public string $label,
        public bool $default = true,
        public string $yes = 'Yes',
        public string $no = 'No',
        public bool|string $required = false,
        public ?Closure $validate = null,
        public string $hint = ''
    ) {
        $this->confirmed = $default;

        $this->on('key', fn ($key) => match ($key) {
            'y' => $this->confirmed = true,
            'n' => $this->confirmed = false,
            Key::TAB, Key::UP, Key::UP_ARROW, Key::DOWN, Key::DOWN_ARROW, Key::LEFT, Key::LEFT_ARROW, Key::RIGHT, Key::RIGHT_ARROW, 'h', 'j', 'k', 'l' => $this->confirmed = ! $this->confirmed,
            Key::ENTER, Key::ENTER_WIN => $this->submit(),
            default => null,
        });
    }
}
