<?php

namespace App\Prompts;

use Closure;
use Laravel\Prompts\SearchPrompt as PromptsSearchPrompt;

class SearchPrompt extends PromptsSearchPrompt
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
        public Closure $options,
        public string $placeholder = '',
        public int $scroll = 5,
        public ?Closure $validate = null,
        public string $hint = ''
    ) {
        $this->trackTypedValue(submit: false);

        $this->on('key', fn ($key) => match ($key) {
            Key::UP, Key::UP_ARROW, Key::SHIFT_TAB => $this->highlightPrevious(),
            Key::DOWN, Key::DOWN_ARROW, Key::TAB => $this->highlightNext(),
            Key::ENTER, Key::ENTER_WIN => $this->highlighted !== null ? $this->submit() : $this->search(),
            Key::LEFT, Key::LEFT_ARROW, Key::RIGHT, Key::RIGHT_ARROW => $this->highlighted = null,
            default => $this->search(),
        });
    }
}
