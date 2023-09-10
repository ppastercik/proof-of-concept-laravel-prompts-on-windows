<?php

namespace App\Prompts;

use Closure;
use Illuminate\Support\Collection;
use Laravel\Prompts\MultiSelectPrompt as PromptsMultiSelectPrompt;

class MultiSelectPrompt extends PromptsMultiSelectPrompt
{
    use Concerns\AllowWindowsTerminal;

    /**
     * Overrided to add ability to use Enter on Windows system.
     *
     * @inheritDoc
     */
    public function __construct(
        public string $label,
        array|Collection $options,
        array|Collection $default = [],
        public int $scroll = 5,
        public bool|string $required = false,
        public ?Closure $validate = null,
        public string $hint = ''
    ) {
        $this->options = $options instanceof Collection ? $options->all() : $options;
        $this->default = $default instanceof Collection ? $default->all() : $default;
        $this->values = $this->default;

        $this->on('key', fn ($key) => match ($key) {
            Key::UP, Key::UP_ARROW, Key::LEFT, Key::LEFT_ARROW, Key::SHIFT_TAB, 'k', 'h' => $this->highlightPrevious(),
            Key::DOWN, Key::DOWN_ARROW, Key::RIGHT, Key::RIGHT_ARROW, Key::TAB, 'j', 'l' => $this->highlightNext(),
            Key::SPACE => $this->toggleHighlighted(),
            Key::ENTER, Key::ENTER_WIN => $this->submit(),
            default => null,
        });
    }
}
