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
                InputOption::VALUE_OPTIONAL,
                'cs,fixtures'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (false === $input->getParameterOption('--check')) {
            return null;
        }

        $this->filterHealthCheckers($input);
        $input->setOption('dry-run', true);

        foreach ($this->healthCheckers as $healthChecker) {
            $this->dispatcher->addSubscriber($healthChecker);
        }

        $this->dispatcher->addSubscriber($this->resultPrinterSubscriber);
        $this->resultInterpreter->registerResultInterpretation($this->resultInterpretation);
    }

    /**
     * Filter health checkers according to parameter value
     * @param InputInterface $input
     * @return void
     */
    private function filterHealthCheckers(InputInterface $input)
    {
        if (null === $input->getOption('check')) {
            return;
        }

        $healthCheckers = array_map('trim', explode(',', $input->getOption('check')));

        foreach ($this->healthCheckers as $key => $healthChecker) {
            if (!in_array($healthChecker->getName(), $healthCheckers)) {
                unset($this->healthCheckers[$key]);
            }
        }
    }
}
