<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation\EventListener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\IsolatorInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Registers test isolators
 */
class TestIsolationSubscriber implements EventSubscriberInterface
{
    const ISOLATOR_THRESHOLD = 500;

    const YES_PATTERN = '/^Y/i';

    /** @var IsolatorInterface[] */
    private array $isolators = [];

    /** @var IsolatorInterface[] */
    private array $reverseIsolators;

    private ?OutputInterface $output = null;

    private ?InputInterface $input = null;

    private Stopwatch $stopwatch;

    private bool $skip = false;

    private array $skipIsolatorsTags = [];

    private KernelInterface $kernel;

    /**
     * @param IsolatorInterface[] $isolators
     */
    public function __construct(array $isolators, KernelInterface $kernel)
    {
        $this->reverseIsolators = $isolators;
        $this->isolators = array_reverse($isolators);
        $this->stopwatch = new Stopwatch();
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ExerciseCompleted::BEFORE => ['beforeExercise', 100],
            BeforeFeatureTested::BEFORE => ['beforeFeature', 100],
            BeforeScenarioTested::BEFORE => ['beforeScenario', 100],
            AfterScenarioTested::AFTER => ['afterScenario', -100],
            AfterFeatureTested::AFTER => ['afterFeature', -100],
            ExerciseCompleted::AFTER => ['afterExercise', -100],
        ];
    }

    public function skip(): void
    {
        $this->skip = true;
    }

    public function skipIsolatorsTags(array $tags)
    {
        $this->skipIsolatorsTags = $tags;
    }

    public function getIsolatorsTags(): array
    {
        return array_unique(array_map(fn ($isolator) => $isolator->getTag(), $this->isolators));
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function beforeExercise()
    {
        if ($this->skip) {
            return;
        }

        foreach ($this->isolators as $isolator) {
            if (in_array($isolator->getTag(), $this->skipIsolatorsTags)) {
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
            if (in_array($isolator->getTag(), $this->skipIsolatorsTags)) {
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

    public function beforeFeature(BeforeFeatureTested $event)
    {
        if (array_search('behat-test-env', $event->getFeature()->getTags()) &&
            $this->kernel->getEnvironment() !== 'behat_test') {
            $this->output->writeln(
                '<error>Tests tagged by @behat-test-env work only in the behat_test application environment</error>'
            );
        }
        if ($this->skip) {
            return;
        }

        $event = new BeforeIsolatedTestEvent($this->output, $event->getFeature());

        foreach ($this->isolators as $isolator) {
            if (in_array($isolator->getTag(), $this->skipIsolatorsTags)) {
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
            if (in_array($isolator->getTag(), $this->skipIsolatorsTags)) {
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

    public function afterExercise(ExerciseCompleted $event)
    {
        if ($this->skip) {
            return;
        }

        $event = new AfterFinishTestsEvent($this->output);

        $this->output->writeln('<comment>Begin clean up isolation environment</comment>');
        foreach ($this->reverseIsolators as $isolator) {
            if (in_array($isolator->getTag(), $this->skipIsolatorsTags)) {
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

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function setInput(InputInterface $input)
    {
        $this->input = $input;
    }
}
