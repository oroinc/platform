<?php

namespace Oro\Component\Log\Logger;

use Symfony\Component\Console\Output\OutputInterface;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class OutputLogger extends AbstractLogger
{
    /** @var OutputInterface */
    protected $output;

    /** @var bool */
    protected $alwaysLogErrors;

    /** @var int|null */
    protected $verbosity;

    /** @var string|null */
    protected $indent;

    /** @var bool   use message level-based templates */
    protected $useTemplate;

    /**
     * Constructor
     *
     * @param OutputInterface $output
     * @param bool            $alwaysLogErrors
     * @param int|null        $verbosity        NULL or OutputInterface::VERBOSITY_*
     * @param string|null     $indent
     * @param bool            $useTemplate
     */
    public function __construct(
        OutputInterface $output,
        $alwaysLogErrors = true,
        $verbosity = null,
        $indent = null,
        $useTemplate = false
    ) {
        $this->output          = $output;
        $this->alwaysLogErrors = $alwaysLogErrors;
        $this->verbosity       = $verbosity;
        $this->indent          = $indent;
        $this->useTemplate     = $useTemplate;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array())
    {
        $verbosity = null !== $this->verbosity
            ? $this->verbosity
            : $this->output->getVerbosity();

        switch ($level) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
                if (!$this->alwaysLogErrors && $verbosity === OutputInterface::VERBOSITY_QUIET) {
                    return;
                }
                break;
            case LogLevel::WARNING:
            case LogLevel::NOTICE:
                if ($verbosity < OutputInterface::VERBOSITY_NORMAL) {
                    return;
                }
                break;
            case LogLevel::INFO:
                if ($verbosity < OutputInterface::VERBOSITY_VERBOSE) {
                    return;
                }
                break;
            case LogLevel::DEBUG:
                if ($verbosity < OutputInterface::VERBOSITY_DEBUG) {
                    return;
                }
                break;
        }

        if ($this->useTemplate && is_string($message)) {
            $message = sprintf($this->getTemplate($level), $level, $message);
        }

        if (!is_null($this->indent) && is_string($message)) {
            $message = $this->indent . $message;
        }

        $this->output->writeln($message);

        // based on PSR-3 recommendations if an Exception object is passed in the context data,
        // it MUST be in the 'exception' key.
        if (isset($context['exception']) && $context['exception'] instanceof \Exception) {
            $this->output->writeln(
                sprintf($this->getTemplate(LogLevel::ERROR), LogLevel::ERROR, (string)$context['exception'])
            );
        }
    }

    /**
     * @param string $level
     *
     * @return string
     */
    protected function getTemplate($level)
    {
        switch ($level) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
                $result = '<error>[%s]</error> [%s]';
                break;
            case LogLevel::WARNING:
                $result = '<comment>[%s]</comment> [%s]';
                break;
            default:
                $result = '<info>[%s]</info> [%s]';
                break;
        }

        return $result;
    }
}
