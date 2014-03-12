<?php

namespace Oro\Bundle\MigrationBundle\Command\Logger;

use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class OutputLogger extends AbstractLogger
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var bool
     */
    protected $alwaysLogErrors;

    /**
     * @var int|null
     */
    protected $verbosity;

    /**
     * @var string|null
     */
    protected $indent;

    /**
     * Constructor
     *
     * @param OutputInterface $output
     * @param bool            $alwaysLogErrors
     * @param int|null        $verbosity NULL or OutputInterface::VERBOSITY_*
     * @param string|null     $indent
     */
    public function __construct(
        OutputInterface $output,
        $alwaysLogErrors = true,
        $verbosity = null,
        $indent = null
    ) {
        $this->output          = $output;
        $this->alwaysLogErrors = $alwaysLogErrors;
        $this->verbosity       = $verbosity;
        $this->indent          = $indent;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function log($level, $message, array $context = array())
    {
        $verbosity = null === $this->verbosity
            ? $this->output->getVerbosity()
            : min($this->verbosity, $this->output->getVerbosity());
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

        $this->output->writeln(sprintf($this->getTemplate($level), $message));

        // based on PSR-3 recommendations if an Exception object is passed in the context data,
        // it MUST be in the 'exception' key.
        if (isset($context['exception']) && $context['exception'] instanceof \Exception) {
            $this->output->writeln(
                sprintf($this->getTemplate(LogLevel::ERROR), (string)$context['exception'])
            );
        }
    }

    /**
     * @param string $level
     * @return string
     */
    protected function getTemplate($level)
    {
        switch ($level) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
                $result = '<error>%s</error>';
                break;
            case LogLevel::WARNING:
                $result = '<comment>%s</comment>';
                break;
            default:
                $result = '<info>%s</info>';
                break;
        }

        return $this->indent
            ? $this->indent . $result
            : $result;
    }
}
