<?php

namespace App\Prompts;

use Closure;
use Illuminate\Support\Collection;
use Laravel\Prompts\SelectPrompt as PromptsSelectPrompt;

class SelectPrompt extends PromptsSelectPrompt
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
        public int|string|null $default = null,
        public int $scroll = 5,
        public ?Closure $validate = null,
        public string $hint = ''
    ) {
        $this->options = $options instanceof Collection ? $options->all() : $options;

        if ($this->default) {
            if (array_is_list($this->options)) {
                $this->highlighted = array_search($this->default, $this->options) ?: 0;
            } else {
                $this->highlighted = array_search($this->default, array_keys($this->options)) ?: 0;
            }
        }

        $this->on('key', fn ($key) => match ($key) {
            Key::UP, Key::UP_ARROW, Key::LEFT, Key::LEFT_ARROW, Key::SHIFT_TAB, 'k', 'h' => $this->highlightPrevious(),
            Key::DOWN, Key::DOWN_ARROW, Key::RIGHT, Key::RIGHT_ARROW, Key::TAB, 'j', 'l' => $this->highlightNext(),
            Key::ENTER, Key::ENTER_WIN => $this->submit(),
            default => null,
        });
    }
}
