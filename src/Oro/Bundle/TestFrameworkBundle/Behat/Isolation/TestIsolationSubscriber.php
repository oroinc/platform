<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Behat\Testwork\EventDispatcher\Event\BeforeExerciseCompleted;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class TestIsolationSubscriber implements EventSubscriberInterface
{
    const YES_PATTERN = '/^Y/i';

    /** @var IsolatorInterface[] */
    protected $isolators;

    /** @var IsolatorInterface[] */
    protected $reverseIsolators;

    /** @var OutputInterface */
    private $output;

    /** @var InputInterface */
    protected $input;

    /**
     * @param IsolatorInterface[] $isolators
     */
    public function __construct(array $isolators, KernelInterface $kernel)
    {
        $kernel->boot();
        $container = $kernel->getContainer();
        $applicableIsolators = [];

        foreach ($isolators as $isolator) {
            if ($isolator->isApplicable($container)) {
                $applicableIsolators[] = $isolator;
            }
        }

        $this->reverseIsolators = $applicableIsolators;
        $this->isolators = array_reverse($applicableIsolators);

        $kernel->shutdown();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeExerciseCompleted::BEFORE => ['beforeExercise', 100],
            BeforeFeatureTested::BEFORE     => ['beforeFeature', 100],
            BeforeScenarioTested::BEFORE    => ['beforeScenario', 100],
            AfterScenarioTested::AFTER      => ['afterScenario', -100],
            AfterFeatureTested::AFTER       => ['afterFeature', -100],
            AfterExerciseCompleted::AFTER   => ['afterExercise', -100]
        ];
    }

    public function beforeExercise()
    {
        foreach ($this->isolators as $isolator) {
            if ($isolator->isOutdatedState()) {
                $helper = new QuestionHelper();
                $question = new ConfirmationQuestion(
                    sprintf(
                        '<question>"%s" isolator discover that last time '.
                        'environment was not be restored properly.'.PHP_EOL
                        .'Do you what to restore state?(Y/n)</question>',
                        $isolator->getName()
                    ),
                    true,
                    self::YES_PATTERN
                );

                if ($helper->ask($this->input, $this->output, $question)) {
                    $isolator->restoreState(new RestoreStateEvent($this->output));
                }
            }
        }

        $event = new BeforeStartTestsEvent($this->output);

        $this->output->writeln('<comment>Begin isolating application state</comment>');
        foreach ($this->isolators as $isolator) {
            $isolator->start($event);
        }
        $this->output->writeln('<comment>Application ready for tests</comment>');
    }

    public function beforeFeature()
    {
        $event = new BeforeIsolatedTestEvent();

        foreach ($this->isolators as $isolator) {
            $isolator->beforeTest($event);
        }
    }

    public function beforeScenario()
    {
    }

    public function afterScenario()
    {
    }

    public function afterFeature()
    {
        $event = new AfterIsolatedTestEvent();

        foreach ($this->reverseIsolators as $isolator) {
            $isolator->afterTest($event);
        }
    }

    public function afterExercise()
    {
        $event = new AfterFinishTestsEvent($this->output);

        $this->output->writeln('<comment>Begin clean up isolation environment</comment>');
        foreach ($this->reverseIsolators as $isolator) {
            $isolator->terminate($event);
        }
        $this->output->writeln('<comment>Isolation environment is clean</comment>');
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }
}
