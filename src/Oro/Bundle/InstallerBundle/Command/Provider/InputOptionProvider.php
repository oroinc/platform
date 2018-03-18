<?php

namespace Oro\Bundle\InstallerBundle\Command\Provider;

use Composer\Question\StrictConfirmationQuestion;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class InputOptionProvider
{
    /** @var OutputInterface */
    protected $output;

    /** @var InputInterface */
    protected $input;

    /** @var QuestionHelper */
    protected $questionHelper;

    /**
     * @param OutputInterface $output
     * @param InputInterface $input
     * @param QuestionHelper $questionHelper
     */
    public function __construct(OutputInterface $output, InputInterface $input, QuestionHelper $questionHelper)
    {
        $this->output = $output;
        $this->input  = $input;
        $this->questionHelper = $questionHelper;
    }

    /**
     * Gets a value of the specified option. If needed an user can be asked to enter the value
     *
     * @param string      $name              The option name
     * @param string      $questionMessage   The ask question message
     * @param string|null $default           The default option value
     *                                       to enter option value
     * @param array       $options           Options which identify question class, args and options
     *
     * @return mixed boolean for ConfirmationQuestion, string for others
     */
    public function get($name, $questionMessage, $default = null, $options = [])
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

    /**
     * @param array $options
     * @return bool
     */
    private function isConfirmationQuestion(array $options)
    {
        if (!isset($options['class'])) {
            return false;
        }

        return is_a($options['class'], ConfirmationQuestion::class, true)
            || is_a($options['class'], StrictConfirmationQuestion::class, true);
    }

    /**
     * @param string $message
     * @param array $options
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

        return $question;
    }

    /**
     * Returns a string represents a question for console dialog helper
     *
     * @param string      $text
     * @param string|null $defaultValue
     *
     * @return string
     */
    protected function buildQuestion($text, $defaultValue = null)
    {
        return empty($defaultValue)
            ? sprintf('<question>%s:</question> ', $text)
            : sprintf('<question>%s (%s):</question> ', $text, $defaultValue);
    }
}
