<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\RuntimeTester;

use Behat\Behat\Definition\Call\DefinitionCall;
use Behat\Behat\Definition\Call\Given;
use Behat\Behat\Definition\DefinitionFinder;
use Behat\Behat\Definition\Exception\SearchException;
use Behat\Behat\Definition\SearchResult;
use Behat\Behat\Tester\Result\ExecutedStepResult;
use Behat\Behat\Tester\Result\FailedStepSearchResult;
use Behat\Behat\Tester\Result\SkippedStepResult;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Behat\Tester\Result\UndefinedStepResult;
use Behat\Behat\Tester\StepTester;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\Call\CallCenter;
use Behat\Testwork\Call\CallResult;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Tester\Setup\Setup;
use Behat\Testwork\Tester\Setup\SuccessfulSetup;
use Behat\Testwork\Tester\Setup\SuccessfulTeardown;
use Behat\Testwork\Tester\Setup\Teardown;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Bundle\TestFrameworkBundle\Behat\Exception\SkipTestExecutionException;
use Oro\Bundle\TestFrameworkBundle\Behat\Provider\WatchModeQuestionProvider;
use Oro\Bundle\TestFrameworkBundle\Behat\Session\Mink\WatchModeSessionHolder;
use Symfony\Component\Process\Process;

/**
 * A copy of {@see \Behat\Behat\Tester\Runtime\RuntimeStepTester} that provides child sub-processes for "--watch" mode.
 */
class RuntimeStepTester implements StepTester
{
    private ?StepTester $stepTester = null;
    private ?WatchModeSessionHolder $sessionHolder = null;
    private ?WatchModeQuestionProvider $questionProvider = null;

    public function __construct(
        private DefinitionFinder $definitionFinder,
        private CallCenter $callCenter,
    ) {
    }

    public function setUp(Environment $env, FeatureNode $feature, StepNode $step, $skip): Setup
    {
        return new SuccessfulSetup();
    }

    public function setStepTester(StepTester $stepTester): void
    {
        $this->stepTester = $stepTester;
    }

    public function setSessionHolder(WatchModeSessionHolder $watchSessionHolder): void
    {
        $this->sessionHolder = $watchSessionHolder;
    }

    public function setQuestionProvider(WatchModeQuestionProvider $questionProvider): void
    {
        $this->questionProvider = $questionProvider;
    }

    public function test(Environment $env, FeatureNode $feature, StepNode $step, $skip = false): StepResult
    {
        try {
            $search = $this->searchDefinition($env, $feature, $step);
            $result = $this->testDefinition($env, $feature, $step, $search, $skip);
        } catch (SearchException $exception) {
            $result = new FailedStepSearchResult($exception);
        }

        return $result;
    }

    public function tearDown(
        Environment $env,
        FeatureNode $feature,
        StepNode $step,
        $skip,
        StepResult $result
    ): Teardown {
        return new SuccessfulTeardown();
    }

    private function searchDefinition(Environment $env, FeatureNode $feature, StepNode $step): SearchResult
    {
        return $this->definitionFinder->findDefinition($env, $feature, $step);
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function testDefinition(
        Environment $env,
        FeatureNode $feature,
        StepNode $step,
        SearchResult $search,
        bool $skip
    ): StepResult {
        /** Customization start */
        if (!$search->hasMatch()) {
            if (!($this->sessionHolder->isWatchMode() || $this->sessionHolder->isWatchFrom())) {
                return new UndefinedStepResult();
            }
        }
        if ($skip) {
            return new SkippedStepResult($search);
        }
        try {
            $call = $this->createDefinitionCall($env, $feature, $search, $step);
            $result = $this->callCenter->makeCall($call);
        } catch (\Throwable $exception) {
            if (!$this->sessionHolder->isWatchMode() && !$this->sessionHolder->isWatchFrom()) {
                throw $exception;
            }
            // override call result to prevent fatal error in --watch mode
            $result = new CallResult(
                new DefinitionCall($env, $feature, $step, new Given('', fn () => '', null), []),
                null,
                new \LogicException(
                    sprintf(
                        'Invalid test step or context. Failed to process test step: `%s`',
                        $step->getText()
                    ),
                    $exception->getCode(),
                    $exception
                )
            );
        }
        if ($this->sessionHolder->isWatchMode() || $this->sessionHolder->isWatchFrom()) {
            $this->sessionHolder->setLastProcessedStep($step->getLine());
        }
        $this->testSubProcessDefinition($result, $search, $env, $feature, $step);

        if ($this->sessionHolder->isWatchMode()
            && !$this->sessionHolder->isWatchFrom()
            && $this->isLastStep($feature, $step)) {
            $this->stepTester->tearDown($env, $feature, $step, false, new ExecutedStepResult($search, $result));
            $endTestStatus = $this->questionProvider->askBeforeTestEnd();
            if ($endTestStatus === 1) {
                $this->testSubProcessDefinition($result, $search, $env, $feature, $step, true);
            }
        }
        if ($this->isLastStep($feature, $step) && $this->sessionHolder->isWatchFrom()) {
            $this->stepTester->tearDown($env, $feature, $step, false, new ExecutedStepResult($search, $result));

            throw new SkipTestExecutionException(2);
        }
        /** Customization end */

        return new ExecutedStepResult($search, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function testSubProcessDefinition(
        CallResult $result,
        SearchResult $search,
        Environment $env,
        FeatureNode $feature,
        StepNode $step,
        bool $skipException = false
    ): void {
        if ($this->sessionHolder->isWatchMode()
            && !$this->sessionHolder->isWatchFrom()
            && (null !== $result->getException() || $skipException)) {
            // tear down failed step
            if (!$skipException) {
                $this->stepTester->tearDown($env, $feature, $step, false, new ExecutedStepResult($search, $result));
            }
            do {
                // run behat sub process for --watch mode
                do {
                    $this->sessionHolder->actualizeState(true);
                    $this->sessionHolder->setLastProcessedStep($step->getLine());
                    $line = $this->questionProvider->askInputStepLine($feature);

                    $statusCode = $this->runChildProcess($feature, $line);
                } while ($statusCode > 0);
                // ask Question after the last test step was passed
                $endTestStatus = $this->questionProvider->askBeforeTestEnd();
            } while ($endTestStatus > 0);
            // skip main process when child process is already successfully done
            throw new SkipTestExecutionException();
        } elseif (null !== $result->getException() && $this->sessionHolder->isWatchFrom()) {
            $this->sessionHolder->setLastProcessedStep($step->getLine());
            $this->stepTester->tearDown($env, $feature, $step, false, new ExecutedStepResult($search, $result));
            // skip failed child process execution
            throw new SkipTestExecutionException(1);
        }
    }

    protected function runChildProcess(FeatureNode $feature, int $line): int
    {
        $process = $this->prepareNewProcess($feature, $line);
        $tags = array_map(fn ($item) => '@' . $item, $feature->getTags());
        $tags = implode(' ', $tags);
        $skip = true;

        return $process->run(function ($type, $buffer) use ($tags, $feature, &$skip) {
            // skip empty buffer, feature tags and failed scenario title
            if (!$skip && !empty($buffer) && !str_contains($buffer, $tags)) {
                // runtime buffer output
                echo $buffer;
            }
            if (str_contains($buffer, $feature->getFile())) {
                $skip = false;
            }
        });
    }

    protected function prepareNewProcess(FeatureNode $feature, int $inputLine): Process
    {
        $process = new Process([
            CommandExecutor::getPhpExecutable(),
            'bin/behat',
            $feature->getFile(),
            '--skip-isolators',
            sprintf('%s=%s', '--watch-from', $inputLine),
            ... $this->sessionHolder->getAdditionalOptions()
        ]);
        $process->setPty(true);
        $process->setTimeout(3600);

        return $process;
    }

    protected function isLastStep(FeatureNode $feature, StepNode $step): bool
    {
        $scenarios = $feature->getScenarios();
        if (empty($scenarios)) {
            return false;
        }
        /** @var ScenarioNode $lastScenario */
        $lastScenarioSteps = end($scenarios)->getSteps();
        $lastStep = end($lastScenarioSteps);

        return $lastStep->getLine() === $step->getLine();
    }

    private function createDefinitionCall(
        Environment $env,
        FeatureNode $feature,
        SearchResult $search,
        StepNode $step
    ): DefinitionCall {
        $definition = $search->getMatchedDefinition();
        $arguments = $search->getMatchedArguments();

        return new DefinitionCall($env, $feature, $step, $definition, $arguments);
    }
}
