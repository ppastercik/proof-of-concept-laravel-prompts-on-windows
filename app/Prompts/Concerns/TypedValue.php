<?php

namespace App\Prompts\Concerns;

use App\Prompts\Key;

trait TypedValue
{
    /**
     * Overrided to add ability to use Enter on Windows system.
     *
     * @inheritDoc
     */
    protected function trackTypedValue(string $default = '', bool $submit = true): void
    {
        $this->typedValue = $default;

        if ($this->typedValue) {
            $this->cursorPosition = mb_strlen($this->typedValue);
        }

        $this->on('key', function ($key) use ($submit) {
            if ($key[0] === "\e") {
                match ($key) {
                    Key::LEFT, Key::LEFT_ARROW => $this->cursorPosition = max(0, $this->cursorPosition - 1),
                    Key::RIGHT, Key::RIGHT_ARROW => $this->cursorPosition = min(mb_strlen($this->typedValue), $this->cursorPosition + 1),
                    Key::DELETE => $this->typedValue = mb_substr($this->typedValue, 0, $this->cursorPosition).mb_substr($this->typedValue, $this->cursorPosition + 1),
                    default => null,
                };

                return;
            }

            // Keys may be buffered.
            foreach (mb_str_split($key) as $key) {
                if (($key === Key::ENTER || $key === Key::ENTER_WIN) && $submit) {
                    $this->submit();

                    return;
                } elseif ($key === Key::BACKSPACE || $key === Key::BACKSPACE_WIN) {
                    if ($this->cursorPosition === 0) {
                        return;
                    }

                    $this->typedValue = mb_substr($this->typedValue, 0, $this->cursorPosition - 1).mb_substr($this->typedValue, $this->cursorPosition);
                    $this->cursorPosition--;
                } elseif (ord($key) >= 32) {
                    $this->typedValue = mb_substr($this->typedValue, 0, $this->cursorPosition).$key.mb_substr($this->typedValue, $this->cursorPosition);
                    $this->cursorPosition++;
                }
            }
        });
    }
}
