<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation\EventListener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Behat\Testwork\EventDispatcher\Event\BeforeExerciseCompleted;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\IsolatorInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\SkipIsolatorsTrait;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class TestIsolationSubscriber implements EventSubscriberInterface
{
    use SkipIsolatorsTrait;

    const ISOLATOR_THRESHOLD = 500;

    const YES_PATTERN = '/^Y/i';

    /** @var IsolatorInterface[] */
    protected $isolators;

    /** @var IsolatorInterface[] */
    protected $reverseIsolators;

    /** @var OutputInterface */
    private $output;

    /** @var InputInterface */
    protected $input;

    /** @var Stopwatch */
    private $stopwatch;

    /**
     * @param IsolatorInterface[] $isolators
     */
    public function __construct(array $isolators)
    {
        $this->reverseIsolators = $isolators;
        $this->isolators = array_reverse($isolators);
        $this->stopwatch = new Stopwatch();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeExerciseCompleted::BEFORE => ['beforeExercise', 100],
            BeforeFeatureTested::BEFORE => ['beforeFeature', 100],
            BeforeScenarioTested::BEFORE => ['beforeScenario', 100],
            AfterScenarioTested::AFTER => ['afterScenario', -100],
            AfterFeatureTested::AFTER => ['afterFeature', -100],
            AfterExerciseCompleted::AFTER => ['afterExercise', -100],
        ];
    }

    public function beforeExercise()
    {
        if ($this->skip) {
            return;
        }

        foreach ($this->isolators as $isolator) {
            if (in_array($isolator->getTag(), $this->skipIsolators)) {
                continue;
            }

            if ($isolator->isOutdatedState()) {
                $helper = new QuestionHelper();
                $question = new ConfirmationQuestion(
                    sprintf(
                        '<question>"%s" isolator discover that last time '.
                        'environment was not restored properly.'.PHP_EOL
                        .'Do you what to restore the state?(Y/n)</question>',
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
            if (in_array($isolator->getTag(), $this->skipIsolators)) {
                continue;
            }

            $this->stopwatch->start($isolator->getTag().'::start');
            try {
                $isolator->start($event);
            } catch (TableNotFoundException $e) {
                break;
            } finally {
                $eventResult = $this->stopwatch->stop($isolator->getTag().'::start');
                if ($eventResult->getDuration() >= self::ISOLATOR_THRESHOLD) {
                    $this->output->writeln(sprintf('time: %s ms', $eventResult->getDuration()));
                }
            }
        }
        $this->output->writeln('<comment>Application ready for tests</comment>');
    }

    /**
     * @param BeforeFeatureTested $event
     */
    public function beforeFeature(BeforeFeatureTested $event)
    {
        if ($this->skip) {
            return;
        }

        $event = new BeforeIsolatedTestEvent($this->output, $event->getFeature());

        foreach ($this->isolators as $isolator) {
            if (in_array($isolator->getTag(), $this->skipIsolators)) {
                continue;
            }

            $this->stopwatch->start($isolator->getTag().'::beforeTest');
            try {
                $isolator->beforeTest($event);
            } catch (TableNotFoundException $e) {
                break;
            } finally {
                $eventResult = $this->stopwatch->stop($isolator->getTag().'::beforeTest');
                if ($eventResult->getDuration() >= self::ISOLATOR_THRESHOLD) {
                    $this->output->writeln(sprintf('time: %s ms', $eventResult->getDuration()));
                }
            }
        }
    }

    public function beforeScenario()
    {
        if ($this->skip) {
            return;
        }
    }

    public function afterScenario()
    {
        if ($this->skip) {
            return;
        }
    }

    public function afterFeature()
    {
        if ($this->skip) {
            return;
        }

        $event = new AfterIsolatedTestEvent($this->output);

        foreach ($this->reverseIsolators as $isolator) {
            if (in_array($isolator->getTag(), $this->skipIsolators)) {
                continue;
            }

            $this->stopwatch->start($isolator->getTag().'::afterTest');
            try {
                $isolator->afterTest($event);
            } catch (TableNotFoundException $e) {
                break;
            } finally {
                $eventResult = $this->stopwatch->stop($isolator->getTag().'::afterTest');
                if ($eventResult->getDuration() >= self::ISOLATOR_THRESHOLD) {
                    $this->output->writeln(sprintf('time: %s ms', $eventResult->getDuration()));
                }
            }
        }
    }

    public function afterExercise()
    {
        if ($this->skip) {
            return;
        }

        $event = new AfterFinishTestsEvent($this->output);

        $this->output->writeln('<comment>Begin clean up isolation environment</comment>');
        foreach ($this->reverseIsolators as $isolator) {
            if (in_array($isolator->getTag(), $this->skipIsolators)) {
                continue;
            }

            $this->stopwatch->start($isolator->getTag().'::terminate');
            try {
                $isolator->terminate($event);
            } catch (TableNotFoundException $e) {
                break;
            } finally {
                $eventResult = $this->stopwatch->stop($isolator->getTag().'::terminate');
                if ($eventResult->getDuration() >= self::ISOLATOR_THRESHOLD) {
                    $this->output->writeln(sprintf('time: %s ms', $eventResult->getDuration()));
                }
            }
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
