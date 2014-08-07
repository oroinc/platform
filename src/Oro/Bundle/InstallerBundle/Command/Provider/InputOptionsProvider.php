<?php

namespace Oro\Bundle\InstallerBundle\Command\Provider;

use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InputOptionsProvider
{
    /** @var OutputInterface */
    protected $output;

    /** @var InputInterface */
    protected $input;

    /** @var DialogHelper */
    protected $dialog;

    public function __construct(OutputInterface $output, InputInterface $input, DialogHelper $dialog)
    {
        $this->output = $output;
        $this->input  = $input;
        $this->dialog = $dialog;
    }

    /**
     * @param string      $name
     * @param string      $question
     * @param string|null $default
     * @param string      $askMethod
     * @param array       $additionalAskArgs
     *
     * @return string
     */
    public function get($name, $question, $default = null, $askMethod = 'ask', $additionalAskArgs = [])
    {
        $hasOption = $this->input->hasOption($name);

        if (false === $hasOption && $this->input->isInteractive()) {
            return call_user_func_array(
                [$this->dialog, $askMethod],
                array_merge(
                    [$this->output, $this->buildQuestion($question)],
                    $additionalAskArgs
                )
            );
        } elseif ($hasOption) {
            return $this->input->getOption($name);
        }

        return $default;
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
