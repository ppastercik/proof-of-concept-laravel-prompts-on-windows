<?php

namespace App\Prompts\Concerns;

use App\Prompts\Key;
use App\Prompts\Terminal;
use Laravel\Prompts\Terminal as PromptsTerminal;

trait AllowWindowsTerminal
{
    /**
     * Overrided to skip checkEnvironment() call.
     *
     * @inheritDoc
     */
    public function prompt(): mixed
    {
        $this->capturePreviousNewLines();

        if (static::shouldFallback()) {
            return $this->fallback();
        }

        register_shutdown_function(function () {
            $this->restoreCursor();
            static::terminal()->restoreTty();
        });

        static::terminal()->setTty('-icanon -isig -echo');
        $this->hideCursor();
        $this->render();

        while (($key = static::terminal()->read()) !== null) {
            $continue = $this->handleKeyPress($key);

            $this->render();

            if ($continue === false || $key === Key::CTRL_C) {
                $this->restoreCursor();
                static::terminal()->restoreTty();

                if ($key === Key::CTRL_C) {
                    static::terminal()->exit();
                }

                return $this->value();
            }
        }
    }

    /**
     * Overrided to set custom Terminal instance, which use special PHP windows support functions.
     *
     * @inheritDoc
     */
    public static function terminal(): PromptsTerminal
    {
        if (!static::$terminal instanceof Terminal) {
            // Also override PromptsTerminal instance if setted earlier by not overrided prompts component.
            static::$terminal = new Terminal();
        }

        return static::$terminal;
    }

    /**
     * Copied because it's private function.
     *
     * Reset the cursor position to the beginning of the previous frame.
     */
    private function resetCursorPosition(): void
    {
        $lines = count(explode(PHP_EOL, $this->prevFrame)) - 1;

        $this->moveCursor(-999, $lines * -1);
    }

    /**
     * Copied because it's private function.
     *
     * Get the difference between two strings.
     *
     * @return array<int>
     */
    private function diffLines(string $a, string $b): array
    {
        if ($a === $b) {
            return [];
        }

        $aLines = explode(PHP_EOL, $a);
        $bLines = explode(PHP_EOL, $b);
        $diff = [];

        for ($i = 0; $i < max(count($aLines), count($bLines)); $i++) {
            if (! isset($aLines[$i]) || ! isset($bLines[$i]) || $aLines[$i] !== $bLines[$i]) {
                $diff[] = $i;
            }
        }

        return $diff;
    }

    /**
     * Copied because it's private function.
     *
     * Handle a key press and determine whether to continue.
     */
    private function handleKeyPress(string $key): bool
    {
        if ($this->state === 'error') {
            $this->state = 'active';
        }

        $this->emit('key', $key);

        if ($this->state === 'submit') {
            return false;
        }

        if ($key === Key::CTRL_C) {
            $this->state = 'cancel';

            return false;
        }

        if ($this->validated) {
            $this->validate($this->value());
        }

        return true;
    }

    /**
     * Copied because it's private function.
     *
     * Validate the input.
     */
    private function validate(mixed $value): void
    {
        $this->validated = true;

        if (($this->required ?? false) && ($value === '' || $value === [] || $value === false)) {
            $this->state = 'error';
            $this->error = is_string($this->required) ? $this->required : 'Required.';

            return;
        }

        if (! isset($this->validate)) {
            return;
        }

        $error = ($this->validate)($value);

        if (! is_string($error) && ! is_null($error)) {
            throw new \RuntimeException('The validator must return a string or null.');
        }

        if (is_string($error) && strlen($error) > 0) {
            $this->state = 'error';
            $this->error = $error;
        }
    }
}
