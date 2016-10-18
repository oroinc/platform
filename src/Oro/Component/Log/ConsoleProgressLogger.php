<?php

namespace Oro\Component\Log;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated Will be removed in 2.0
 */
class ConsoleProgressLogger implements ProgressLoggerInterface
{
    /** @var OutputInterface */
    protected $output;

    /** @var ProgressBar */
    protected $progressBar;

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function logAdvance($step)
    {
        if (!$this->progressBar) {
            throw new \RuntimeException('Trying to log advance without logging steps first.');
        }

        $this->progressBar->advance($step);
    }

    /**
     * {@inheritdoc}
     */
    public function logFinish()
    {
        if (!$this->progressBar) {
            throw new \RuntimeException('Trying to log finish without logging steps first.');
        }

        $this->progressBar->finish();
        $this->output->writeln('');
    }

    /**
     * {@inheritdoc}
     */
    public function logSteps($steps)
    {
        $this->progressBar = new ProgressBar($this->output, $steps);
    }
}
