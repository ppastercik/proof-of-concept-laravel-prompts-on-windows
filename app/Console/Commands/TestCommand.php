<?php

namespace App\Console\Commands;

use App\Prompts\ConfirmPrompt;
use App\Prompts\MultiSelectPrompt;
use App\Prompts\PasswordPrompt;
use App\Prompts\SearchPrompt;
use App\Prompts\SelectPrompt;
use App\Prompts\SuggestPrompt;
use App\Prompts\TextPrompt;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Prompts\Note;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\Spinner;
use Laravel\Prompts\Themes\Default\ConfirmPromptRenderer;
use Laravel\Prompts\Themes\Default\MultiSelectPromptRenderer;
use Laravel\Prompts\Themes\Default\NoteRenderer;
use Laravel\Prompts\Themes\Default\PasswordPromptRenderer;
use Laravel\Prompts\Themes\Default\SearchPromptRenderer;
use Laravel\Prompts\Themes\Default\SelectPromptRenderer;
use Laravel\Prompts\Themes\Default\SpinnerRenderer;
use Laravel\Prompts\Themes\Default\SuggestPromptRenderer;
use Laravel\Prompts\Themes\Default\TextPromptRenderer;
use Symfony\Component\Console\Input\InputInterface;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testing laravel/prompts with a special build of PHP for Windows that allows interactivity on Windows command lines';

    /**
     * Override to set fallback when not use PHP binary with special windows support functions on Windows OS.
     *
     * @inheritDoc
     */
    protected function configurePrompts(InputInterface $input): void
    {
        Prompt::setOutput($this->output);

        Prompt::fallbackWhen(! $input->isInteractive() || $this->useFallbackOnWindows() || $this->laravel->runningUnitTests());

        TextPrompt::fallbackUsing(fn (TextPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->components->ask($prompt->label, $prompt->default ?: null) ?? '',
            $prompt->required,
            $prompt->validate
        ));

        PasswordPrompt::fallbackUsing(fn (PasswordPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->components->secret($prompt->label) ?? '',
            $prompt->required,
            $prompt->validate
        ));

        ConfirmPrompt::fallbackUsing(fn (ConfirmPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->components->confirm($prompt->label, $prompt->default),
            $prompt->required,
            $prompt->validate
        ));

        SelectPrompt::fallbackUsing(fn (SelectPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->components->choice($prompt->label, $prompt->options, $prompt->default),
            false,
            $prompt->validate
        ));

        MultiSelectPrompt::fallbackUsing(function (MultiSelectPrompt $prompt) {
            if ($prompt->default !== []) {
                return $this->promptUntilValid(
                    fn () => $this->components->choice($prompt->label, $prompt->options, implode(',', $prompt->default), multiple: true),
                    $prompt->required,
                    $prompt->validate
                );
            }

            return $this->promptUntilValid(
                fn () => collect($this->components->choice($prompt->label, ['' => 'None', ...$prompt->options], 'None', multiple: true))
                    ->reject('')
                    ->all(),
                $prompt->required,
                $prompt->validate
            );
        });

        SuggestPrompt::fallbackUsing(fn (SuggestPrompt $prompt) => $this->promptUntilValid(
            fn () => $this->components->askWithCompletion($prompt->label, $prompt->options, $prompt->default ?: null) ?? '',
            $prompt->required,
            $prompt->validate
        ));

        SearchPrompt::fallbackUsing(fn (SearchPrompt $prompt) => $this->promptUntilValid(
            function () use ($prompt) {
                $query = $this->components->ask($prompt->label);

                $options = ($prompt->options)($query);

                return $this->components->choice($prompt->label, $options);
            },
            false,
            $prompt->validate
        ));

        Prompt::addTheme('custom', [
            TextPrompt::class => TextPromptRenderer::class,
            PasswordPrompt::class => PasswordPromptRenderer::class,
            SelectPrompt::class => SelectPromptRenderer::class,
            MultiSelectPrompt::class => MultiSelectPromptRenderer::class,
            ConfirmPrompt::class => ConfirmPromptRenderer::class,
            SearchPrompt::class => SearchPromptRenderer::class,
            SuggestPrompt::class => SuggestPromptRenderer::class,
            Spinner::class => SpinnerRenderer::class,
            Note::class => NoteRenderer::class,
        ]);

        Prompt::theme('custom');
    }

    /**
     * Determine if use fallback on Windows OS or not.
     *
     * Fallback when not has windows support function or stty available (some terminals emulates stty).
     */
    protected function useFallbackOnWindows(): bool
    {
        return windows_os() && stream_isatty(STDIN) && (!$this->hasWindowsSupportFunctions() && !shell_exec('stty 2> '.('\\' === \DIRECTORY_SEPARATOR ? 'NUL' : '/dev/null')));
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        intro('Platform');

        note(PHP_OS_FAMILY);

        intro('PHP has special windows support functions');

        if ($this->hasWindowsSupportFunctions()) {
            info('Yes');
        } else {
            error('No');
        }

        intro('STDIN is a TTY');

        if (stream_isatty(STDIN)) {
            info('Yes');
        } else {
            error('No');
        }

        intro('Using special handling with special windows support functions');

        if (PHP_OS_FAMILY === 'Windows' && stream_isatty(STDIN) && $this->hasWindowsSupportFunctions()) {
            info('Yes');
        } else {
            error('No');
        }

        $value = (new TextPrompt(
            label: 'Write some text:',
            placeholder: 'WRITE HERE',
            default: 'Some text',
            required: 'Value required to continue.',
            hint: 'Must input a text'
        ))->prompt();

        info($value);

        $value = (new PasswordPrompt(
            label: 'Write some password:',
            placeholder: 'Minimum X characters...',
            required: 'Password is required to continue.',
            hint: 'Must input a password'
        ))->prompt();

        info($value);

        $value = (new ConfirmPrompt(
            label: 'Do you confirm that?',
            yes: 'I accept',
            no: 'I decline',
            required: 'You must accept to continue.',
            hint: 'You must accept this'
        ))->prompt();

        info($value);

        $value = (new SelectPrompt(
            label: 'Choose your favorit number:',
            options: ['One', 'Two', 'Three', 'Four', 'Five'],
            default: 'Two',
            scroll: 3,
            hint: 'Choose a number'
        ))->prompt();

        info($value);

        $value = (new MultiSelectPrompt(
            label: 'Choose some numbers:',
            options: ['One', 'Two', 'Three', 'Four', 'Five'],
            default: ['One', 'Three'],
            scroll: 3,
            required: 'At least one number is required to continue',
            hint: 'Must choose at least one number'
        ))->prompt();

        info(implode(', ', $value));

        $value = (new SuggestPrompt(
            label: 'Choose some numbers:',
            options: ['1', '2', '3', '4', '5', 'One', 'Two', 'Three', 'Four', 'Five'],
            placeholder: 'WRITE NUMBER',
            required: 'Number is required to continue',
            hint: 'Must input a number'
        ))->prompt();

        info($value);

        $names = ["Abigail","Aiden","Alexander","Alice","Amelia","Ava","Avery","Benjamin","Carter","Charlotte","Chloe","Christopher","Daniel","David","Ella","Emily","Emily","Emma","Ethan","Evelyn","Grace","Harper","Henry","Isabella","Jackson","James","Jayden","John","Joseph","Layla","Leo","Liam","Lily","Logan","Lucas","Luna","Madison","Mateo","Matthew","Mia","Michael","Muhammad","Olivia","Samuel","Scarlett","Sebastian","Sofia","Sophia","William","Zoe"];

        $value = (new SearchPrompt(
            label: 'Write some name:',
            options: fn (?string $value) => strlen($value) > 0
                ? collect($names)->where(fn (string $item) => Str::startsWith(mb_strtolower($item), [mb_strtolower($value)]))->values()->all()
                : $names,
            placeholder: 'WRITE NAME',
            scroll: 3,
            hint: 'Must input a number'
        ))->prompt();

        info($value);
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
}
