<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\TestIsolationSubscriber;
use Oro\Bundle\TestFrameworkBundle\Behat\Listener\FixturesSubscriber;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InputOutputController implements Controller
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
        $this->testIsolationSubscriber->setInput($input);
        $this->testIsolationSubscriber->setOutput($output);
        $this->testIsolationSubscriber->setSkip($skip);
    }
}
