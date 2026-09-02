<?php

declare(strict_types=1);

namespace Nexus\Cli;

final class ConsolePrompter implements PrompterInterface
{
    /** @var resource */
    private mixed $input;

    /** @param resource|null $input */
    public function __construct(
        private readonly OutputInterface $output,
        mixed $input = null,
    ) {
        $this->input = $input ?? STDIN;

        if (!is_resource($this->input)) {
            throw new \InvalidArgumentException('Console prompts require a readable stream.');
        }
    }

    public function ask(string $question, ?string $default = null): string
    {
        $suffix = $default === null ? ': ' : sprintf(' [%s]: ', $default);
        $this->output->write($question . $suffix);
        $answer = fgets($this->input);

        if ($answer === false) {
            return $default ?? '';
        }

        $answer = trim($answer);

        return $answer === '' && $default !== null ? $default : $answer;
    }

    public function choose(string $question, array $choices, ?string $default = null): string
    {
        $this->output->writeln($question);
        $keys = array_keys($choices);

        foreach ($keys as $index => $key) {
            $this->output->writeln(sprintf('  %d) %s', $index + 1, $choices[$key]));
        }

        while (true) {
            $answer = $this->ask('Select an option', $default);

            if (isset($choices[$answer])) {
                return $answer;
            }

            if (ctype_digit($answer)) {
                $selected = $keys[(int) $answer - 1] ?? null;

                if ($selected !== null) {
                    return $selected;
                }
            }

            $this->output->writeln('Invalid selection. Try again.');
        }
    }
}
