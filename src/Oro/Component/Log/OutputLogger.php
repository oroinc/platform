<?php

namespace Oro\Component\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class OutputLogger
 * Allow log message to OutputInterface, e.g. cli
 *
 * @package Oro\Component\Log
 */
class OutputLogger extends AbstractLogger
{
    /** @var OutputInterface */
    protected $output;

    /** @var int|null */
    protected $verbosity;

    /** @var string|null */
    protected $indent;

    /** @var bool */
    protected $useTags;

    /** @var array */
    protected $verbositySettings = [];

    /**
     * Constructor
     *
     * @param OutputInterface $output           output object, e.g. cli output
     * @param bool            $alwaysLogErrors  always log messages with level more than ERROR
     * @param int|null        $verbosity        verbosity level, NULL or OutputInterface::VERBOSITY_*
     * @param string|null     $indent           indent string prefix - allow to prepend some string to all messages
     * @param bool            $useTags          use message level-based output tags
     */
    public function __construct(
        OutputInterface $output,
        $alwaysLogErrors = true,
        $verbosity = null,
        $indent = null,
        $useTags = false
    ) {
        $this->output = $output;
        $this->verbosity = $verbosity;
        $this->indent = $indent;
        $this->useTags = $useTags;

        $this->verbositySettings = [
            OutputInterface::VERBOSITY_QUIET => [
                LogLevel::EMERGENCY => false || $alwaysLogErrors,
                LogLevel::CRITICAL => false || $alwaysLogErrors,
                LogLevel::ERROR => false || $alwaysLogErrors,
                LogLevel::INFO => false,
                LogLevel::WARNING => false,
                LogLevel::ALERT => false,
                LogLevel::NOTICE => false,
                LogLevel::DEBUG => false
            ],
            OutputInterface::VERBOSITY_NORMAL => [
                LogLevel::EMERGENCY => true,
                LogLevel::CRITICAL => true,
                LogLevel::ERROR => true,
                LogLevel::INFO => true,
                LogLevel::WARNING => true,
                LogLevel::ALERT => false,
                LogLevel::NOTICE => false,
                LogLevel::DEBUG => false
            ],
            OutputInterface::VERBOSITY_VERBOSE => [
                LogLevel::EMERGENCY => true,
                LogLevel::CRITICAL => true,
                LogLevel::ERROR => true,
                LogLevel::INFO => true,
                LogLevel::WARNING => true,
                LogLevel::ALERT => false,
                LogLevel::NOTICE => false,
                LogLevel::DEBUG => false
            ],
            OutputInterface::VERBOSITY_VERY_VERBOSE => [
                LogLevel::EMERGENCY => true,
                LogLevel::CRITICAL => true,
                LogLevel::ERROR => true,
                LogLevel::INFO => true,
                LogLevel::WARNING => true,
                LogLevel::ALERT => true,
                LogLevel::NOTICE => true,
                LogLevel::DEBUG => false
            ],
            OutputInterface::VERBOSITY_DEBUG => [
                LogLevel::EMERGENCY => true,
                LogLevel::CRITICAL => true,
                LogLevel::ERROR => true,
                LogLevel::ALERT => true,
                LogLevel::INFO => true,
                LogLevel::WARNING => true,
                LogLevel::NOTICE => true,
                LogLevel::DEBUG => true
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array())
    {
        if (empty($this->verbositySettings[$this->getVerbosity()][$level])) {
            return;
        }

        $this->output->writeln($this->formatMessage($level, $message));

        // based on PSR-3 recommendations if an Exception object is passed in the context data,
        // it MUST be in the 'exception' key.
        if (array_key_exists('exception', $context) && $context['exception'] instanceof \Exception) {
            $this->output->writeln(
                sprintf($this->formatMessage(LogLevel::ERROR, $message), LogLevel::ERROR, (string)$context['exception'])
            );
        }
    }

    /**
     * Return verbosity level
     *
     * @return int
     */
    protected function getVerbosity()
    {
        return null === $this->verbosity
            ? $this->output->getVerbosity()
            : $this->verbosity;
    }

    /**
     * @param string $level
     * @param string $message
     *
     * @return string
     */
    protected function formatMessage($level, $message)
    {
        if ($this->useTags && is_string($message)) {
            $message = sprintf($this->getMessageTemplate($level), $level, $message);
        }

        if (!is_null($this->indent) && is_string($message)) {
            $message = $this->indent . $message;
        }

        return $message;
    }


    /**
     * @param string $level
     *
     * @return string
     */
    protected function getMessageTemplate($level)
    {
        switch ($level) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
                $result = '<error>[%s]</error> %s';
                break;
            case LogLevel::WARNING:
                $result = '<comment>[%s]</comment> %s';
                break;
            default:
                $result = '<info>[%s]</info> %s';
                break;
        }

        return $result;
    }
}
