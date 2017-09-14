<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\EventListener\TestIsolationSubscriber;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SkipController implements Controller
{
    /** @var  TestIsolationSubscriber*/
    protected $testIsolationSubscriber;

    public function __construct(TestIsolationSubscriber $testIsolationSubscriber)
    {
        $this->testIsolationSubscriber = $testIsolationSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var bool $skip */
        $skip = $input->getOption('dry-run');
        $this->testIsolationSubscriber->setSkip($skip);
    }
}
