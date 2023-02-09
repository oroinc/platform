<?php
declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Command\Provider;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Provides functionality for getting input values from the application console when a command is executed.
 */
class InputOptionProvider
{
    protected OutputInterface $output;
    protected InputInterface $input;
    protected QuestionHelper $questionHelper;

    public function __construct(OutputInterface $output, InputInterface $input, QuestionHelper $questionHelper)
    {
        $this->output = $output;
        $this->input  = $input;
        $this->questionHelper = $questionHelper;
    }

    /**
     * Gets a value of a specified option. If needed a user can be asked to enter the value
     *
     * @param string      $name              The option name
     * @param string      $questionMessage   The ask question message
     * @param string|null $default           The default option value
     *                                       to enter option value
     * @param array       $options           Options which identify question class, args and options
     *
     * @return mixed boolean for ConfirmationQuestion, string for others
     */
    public function get(string $name, string $questionMessage, ?string $default = null, array $options = [])
    {
        $value          = $this->input->getOption($name);
        $hasOptionValue = !empty($value);

        // special case for askConfirmation
        if ($hasOptionValue && $this->isConfirmationQuestion($options)) {
            if (in_array(strtolower($value[0]), ['y', 'n'])) {
                $value = strtolower($value[0]) === 'y';
            } else {
                $value          = null;
                $hasOptionValue = false;
            }
        }

        if (false === $hasOptionValue && $this->input->isInteractive()) {
            $question = $this->createQuestion(
                $this->buildQuestion($questionMessage, $default),
                $options
            );

            $value = $this->questionHelper->ask($this->input, $this->output, $question);

            if (empty($value)) {
                $value = $default;
            }
        } elseif (false === $hasOptionValue) {
            $value = $default;
        }

        return $value;
    }

    public function getCommandParametersFromOptions(array $options): array
    {
        $commandParameters = [];
        foreach ($options as $optionName => $optionData) {
            $commandParameters['--' . $optionName] = $this->get(
                $optionName,
                $optionData['label'],
                $optionData['defaultValue'],
                $optionData['options']
            );
        }

        return $commandParameters;
    }

    private function isConfirmationQuestion(array $options): bool
    {
        if (!isset($options['class'])) {
            return false;
        }

        return is_a($options['class'], ConfirmationQuestion::class, true);
    }

    /**
     * @return mixed
     */
    private function createQuestion(string $message, array $options)
    {
        $questionClass = $options['class'] ?? Question::class;
        $constructorArgs = array_merge([$message], $options['constructorArgs'] ?? []);

        $question = new $questionClass(...$constructorArgs);

        $settings = $options['settings'] ?? [];
        foreach ($settings as $setterName => $parameters) {
            $question->{'set'. ucfirst($setterName)}(...$parameters);
        }

        $question->setMaxAttempts(5);

        return $question;
    }

    /**
     * Returns a string represents a question for console dialog helper
     */
    protected function buildQuestion(string $text, ?string $defaultValue = null): string
    {
        return empty($defaultValue)
            ? sprintf('<question>%s:</question> ', $text)
            : sprintf('<question>%s (%s):</question> ', $text, $defaultValue);
    }
}
