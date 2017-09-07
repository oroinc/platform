<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Behat\Testwork\Tester\Result\ResultInterpreter;
use Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker\HealthCheckerInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker\ResultInterpretation;
use Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker\ResultPrinterSubscriber;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class HealthCheckController implements Controller
{
    /**
     * @var HealthCheckerInterface[]
     */
    protected $healthCheckers = [];

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var ResultInterpreter
     */
    protected $resultInterpreter;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param ResultInterpreter $resultInterpreter
     */
    public function __construct(EventDispatcherInterface $dispatcher, ResultInterpreter $resultInterpreter)
    {
        $this->dispatcher = $dispatcher;
        $this->resultInterpreter = $resultInterpreter;
    }

    /**
     * @param HealthCheckerInterface $healthChecker
     */
    public function addHealthChecker(HealthCheckerInterface $healthChecker)
    {
        $this->healthCheckers[] = $healthChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption('--check', null, InputOption::VALUE_NONE,
                'Check behat tests without executing'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('check')) {
            return null;
        }

        $input->setOption('dry-run', true);

        foreach ($this->healthCheckers as $healthChecker) {
            $this->dispatcher->addSubscriber($healthChecker);
        }

        $this->dispatcher->addSubscriber(new ResultPrinterSubscriber($this->healthCheckers, $output));
        $this->resultInterpreter->registerResultInterpretation(new ResultInterpretation($this->healthCheckers));
    }
}
