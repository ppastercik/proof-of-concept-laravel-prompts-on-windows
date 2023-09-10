<?php

namespace App\Prompts;

use Closure;
use Illuminate\Support\Collection;
use Laravel\Prompts\SuggestPrompt as PromptsSuggestPrompt;

class SuggestPrompt extends PromptsSuggestPrompt
{
    use Concerns\AllowWindowsTerminal;
    use Concerns\TypedValue;

    /**
     * Overrided to add ability to use Enter on Windows system.
     *
     * @inheritDoc
     */
    public function __construct(
        public string $label,
        array|Collection|Closure $options,
        public string $placeholder = '',
        public string $default = '',
        public int $scroll = 5,
        public bool|string $required = false,
        public ?Closure $validate = null,
        public string $hint = ''
    ) {
        $this->options = $options instanceof Collection ? $options->all() : $options;

        $this->on('key', fn ($key) => match ($key) {
            Key::UP, Key::UP_ARROW, Key::SHIFT_TAB => $this->highlightPrevious(),
            Key::DOWN, Key::DOWN_ARROW, Key::TAB => $this->highlightNext(),
            Key::ENTER, Key::ENTER_WIN => $this->selectHighlighted(),
            Key::LEFT, Key::LEFT_ARROW, Key::RIGHT, Key::RIGHT_ARROW => $this->highlighted = null,
            default => (function () {
                $this->highlighted = null;
                $this->matches = null;
            })(),
        });

        $this->trackTypedValue($default);
    }
}
