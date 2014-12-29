<?php

namespace Oro\Bundle\InstallerBundle\Command\Provider;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InputOptionProvider
{
    /** @var OutputInterface */
    protected $output;

    /** @var InputInterface */
    protected $input;

    /** @var HelperInterface */
    protected $dialog;

    /**
     * @param OutputInterface $output
     * @param InputInterface  $input
     * @param HelperInterface $dialog
     */
    public function __construct(OutputInterface $output, InputInterface $input, HelperInterface $dialog)
    {
        $this->output = $output;
        $this->input  = $input;
        $this->dialog = $dialog;
    }

    /**
     * Gets a value of the specified option. If needed an user can be asked to enter the value
     *
     * @param string      $name              The option name
     * @param string      $question          The ask question message
     * @param string|null $default           The default option value
     * @param string      $askMethod         The name of method in DialogHelper class is used to ask user
     *                                       to enter option value
     * @param array       $additionalAskArgs The additional arguments for the $askMethod
     *
     * @return mixed boolean for askConfirmation, string for others
     */
    public function get($name, $question, $default = null, $askMethod = 'ask', $additionalAskArgs = [])
    {
        $value          = $this->input->getOption($name);
        $hasOptionValue = !empty($value);

        // special case for askConfirmation
        if ($hasOptionValue && $askMethod === 'askConfirmation') {
            if (in_array(strtolower($value[0]), ['y', 'n'])) {
                $value = strtolower($value[0]) === 'y';
            } else {
                $value          = null;
                $hasOptionValue = false;
            }
        }

        if (false === $hasOptionValue && $this->input->isInteractive()) {
            $value = call_user_func_array(
                [$this->dialog, $askMethod],
                array_merge(
                    [$this->output, $this->buildQuestion($question, $default)],
                    $additionalAskArgs
                )
            );

            if (empty($value)) {
                $value = $default;
            }
        } elseif (false === $hasOptionValue) {
            $value = $default;
        }

        return $value;
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
