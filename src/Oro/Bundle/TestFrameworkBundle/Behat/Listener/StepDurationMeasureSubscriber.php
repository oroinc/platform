<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\Definition\DefinitionFinder;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StepDurationMeasureSubscriber implements EventSubscriberInterface
{
    /**
     * @var float
     */
    protected $startTime;

    /**
     * @var array
     */
    protected $results = [];

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var DefinitionFinder
     */
    protected $definitionFinder;

    /**
     * @param DefinitionFinder $definitionFinder
     */
    public function __construct(DefinitionFinder $definitionFinder)
    {
        $this->definitionFinder = $definitionFinder;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeStepTested::BEFORE  => ['beforeStep', 999],
            AfterStepTested::AFTER  => ['afterStep', 999],
            AfterExerciseCompleted::AFTER => ['printResults', -999],
        ];
    }

    public function beforeStep()
    {
        $this->startTime = microtime(true);
    }

    public function afterStep(AfterStepTested $event)
    {
        if (false === $event->getTestResult()->isPassed()) {
            return;
        }

        $timeEnd = microtime(true);
        $time = $timeEnd - $this->startTime;

        $this->results[] = [
            'time' => $time,
            'env' => $event->getEnvironment(),
            'feature' => $event->getFeature(),
            'step' => $event->getStep(),
        ];
    }

    public function printResults()
    {
        $table = new Table($this->output);
        $results = [];

        foreach ($this->results as $result) {
            $callable = $this->definitionFinder
                ->findDefinition($result['env'], $result['feature'], $result['step'])
                ->getMatchedDefinition()
                ->getCallable()
            ;

            $results[$callable[0].'::'.$callable[1]][] = $result['time'];
        }

        $tableResults = [];

        foreach ($results as $step => $times) {
            $numberCalls = count($times);
            $summeredTime = array_sum($times);

            $tableResults[] = [
                'Step' => $step,
                'Summered time' => $summeredTime,
                'Number Call' => $numberCalls,
                'Average time' => $summeredTime/$numberCalls,
            ];
        }

        usort($tableResults, function ($a, $b) {
            if ($a['Average time'] === $b['Average time']) {
                return 0;
            }
            return ($a['Average time'] > $b['Average time']) ? -1 : 1;
        });

        $table
            ->setHeaders(array_keys($tableResults[0]))
            ->setRows($tableResults)
        ;

        $table->render();
    }
}
