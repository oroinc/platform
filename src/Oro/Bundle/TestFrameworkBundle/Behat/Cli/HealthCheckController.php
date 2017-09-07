<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Behat\Testwork\Tester\Result\ResultInterpreter;
use Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker\HealthCheckerAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker\HealthCheckerAwareTrait;
use Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker\HealthCheckerInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker\ResultInterpretation;
use Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker\ResultPrinterSubscriber;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class HealthCheckController implements Controller, HealthCheckerAwareInterface
{
    use HealthCheckerAwareTrait;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var ResultInterpreter
     */
    protected $resultInterpreter;

    /**
     * @var ResultPrinterSubscriber
     */
    protected $resultPrinterSubscriber;

    /**
     * @var ResultInterpretation
     */
    protected $resultInterpretation;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param ResultInterpreter $resultInterpreter
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        ResultInterpreter $resultInterpreter,
        ResultPrinterSubscriber $resultPrinterSubscriber,
        ResultInterpretation $resultInterpretation
    ) {
        $this->dispatcher = $dispatcher;
        $this->resultInterpreter = $resultInterpreter;
        $this->resultPrinterSubscriber = $resultPrinterSubscriber;
        $this->resultInterpretation = $resultInterpretation;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption(
                '--check',
                null,
                InputOption::VALUE_NONE,
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

        $this->dispatcher->addSubscriber($this->resultPrinterSubscriber);
        $this->resultInterpreter->registerResultInterpretation($this->resultInterpretation);
    }
}
