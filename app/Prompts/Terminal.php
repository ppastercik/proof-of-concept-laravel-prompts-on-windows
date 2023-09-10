<?php

namespace App\Prompts;

use Illuminate\Support\Str;
use Laravel\Prompts\Terminal as PromptsTerminal;

class Terminal extends PromptsTerminal
{
    /**
     * The initial TTY mode.
     */
    protected ?string $initialTtyMode = null;

    /**
     * The win initial TTY mode.
     */
    protected ?int $initialWinTtyMode = null;

    /**
     * Set the TTY mode.
     */
    public function setTty(string $mode): void
    {
        if (windows_os() && stream_isatty(STDIN) && $this->hasWindowsSupportFunctions()) {
            if ($this->initialWinTtyMode === null) {
                $this->initialWinTtyMode |= sapi_windows_echo_input_support(STDIN) ? 1 : 0;
                $this->initialWinTtyMode |= sapi_windows_line_input_support(STDIN) ? 2 : 0;
                $this->initialWinTtyMode |= sapi_windows_processed_input_support(STDIN) ? 4 : 0;
                $this->initialWinTtyMode |= sapi_windows_vt100_input_support(STDIN) ? 8 : 0;
            }

            $echoMode = Str::contains($mode, 'echo') ? !Str::contains($mode, '-echo') : null;
            // Transform isig to processed mode on Windows.
            $processedMode = Str::contains($mode, 'isig') ? !Str::contains($mode, '-isig') : null;
            // Transform icanon to line and VT100 mode on Windows.
            $lineAndVt100Mode = Str::contains($mode, 'icanon') ? !Str::contains($mode, '-icanon') : null;

            if ($echoMode !== null) {
                sapi_windows_echo_input_support(STDIN, $echoMode);
            }
            if ($lineAndVt100Mode !== null) {
                sapi_windows_line_input_support(STDIN, $lineAndVt100Mode);
            }
            // Need to call echo again, because if echo and line is disabled and will be enabled,
            // then first call to `sapi_windows_echo_input_support` will fail because it's not
            // allowed to use enabled echo with disabled line.
            if ($echoMode !== null) {
                sapi_windows_echo_input_support(STDIN, $echoMode);
            }
            if ($processedMode !== null) {
                sapi_windows_processed_input_support(STDIN, $processedMode);
            }
            if ($lineAndVt100Mode !== null) {
                sapi_windows_vt100_input_support(STDIN, !$lineAndVt100Mode);
            }
        } else {
            parent::setTty($mode);
        }
    }

    /**
     * Determine if PHP has windows support functions:
     * - sapi_windows_echo_input_support
     * - sapi_windows_line_input_support
     * - sapi_windows_processed_input_support
     * - sapi_windows_vt100_input_support
     */
    protected function hasWindowsSupportFunctions(): bool
    {
        return \function_exists('sapi_windows_echo_input_support')
            && \function_exists('sapi_windows_line_input_support')
            && \function_exists('sapi_windows_processed_input_support')
            && \function_exists('sapi_windows_vt100_input_support');
    }

    /**
     * Restore the initial TTY mode.
     */
    public function restoreTty(): void
    {
        parent::restoreTty();

        if ($this->initialWinTtyMode !== null) {
            sapi_windows_echo_input_support(STDIN, (bool) ($this->initialWinTtyMode & 1));
            sapi_windows_line_input_support(STDIN, (bool) ($this->initialWinTtyMode & 2));
            // Need to call echo again, because if echo and line is disabled and will be enabled,
            // then first call to `sapi_windows_echo_input_support` will fail because it's not
            // allowed to use enabled echo with disabled line.
            sapi_windows_echo_input_support(STDIN, (bool) ($this->initialWinTtyMode & 1));
            sapi_windows_processed_input_support(STDIN, (bool) ($this->initialWinTtyMode & 4));
            sapi_windows_vt100_input_support(STDIN, (bool) ($this->initialWinTtyMode & 8));
        }
    }
}
