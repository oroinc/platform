<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer;

use Behat\Behat\Tester\Result\ExecutedStepResult;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Behat\Tester\StepTester;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\Call\CallResult;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Exception\ExceptionPresenter;
use Behat\Testwork\Tester\Setup\Setup;
use Behat\Testwork\Tester\Setup\Teardown;
use Oro\Bundle\TestFrameworkBundle\Behat\Storage\FailedFeatures;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\SilencedFailureRepository;

/**
 * Tester executing step tests in the runtime with skip all feature steps if some step failed.
 */
class SkipOnFailureStepTester implements StepTester
{
    const string SILENCED_STEP_MESSAGE_PREFIX = "Step is silenced: \n";

    public function __construct(
        protected StepTester $baseTester,
        protected FailedFeatures $failedFeatures,
        protected ExceptionPresenter $exceptionPresenter,
        protected ?SilencedFailureRepository $silencedFailureRepository
    ) {
    }

    #[\Override]
    public function setUp(Environment $env, FeatureNode $feature, StepNode $step, $skip): Setup
    {
        return $this->baseTester->setUp($env, $feature, $step, $skip);
    }

    #[\Override]
    public function test(Environment $env, FeatureNode $feature, StepNode $step, $skip): StepResult
    {
        if ($this->failedFeatures->isFailureFeature($feature->getTitle())) {
            $skip = true;
        }

        $stepResult = $this->baseTester->test($env, $feature, $step, $skip);
        if (!$stepResult instanceof ExecutedStepResult || !$stepResult->hasException()) {
            return $stepResult;
        }
        return $this->processSilences($stepResult);
    }

    #[\Override]
    public function tearDown(
        Environment $env,
        FeatureNode $feature,
        StepNode $step,
        $skip,
        StepResult $result
    ): Teardown {
        if (!$result->isPassed()) {
            $this->failedFeatures->addFailureFeature($feature->getTitle());
            $skip = true;
        }

        return $this->baseTester->tearDown($env, $feature, $step, $skip, $result);
    }

    private function processSilences(ExecutedStepResult $stepResult): ExecutedStepResult
    {
        if (null === $this->silencedFailureRepository) {
            return $stepResult;
        }
        $call = $stepResult->getCallResult()->getCall();
        $step = $call->getStep();
        $feature = $call->getFeature();
        $title = $feature->getTitle();
        $message = $step->getKeyword() . ' ' . $step->getText();
        $message .= ': ' . $this->exceptionPresenter->presentException($stepResult->getException());
        $isSilenced = $this->silencedFailureRepository->isSilencedCase($title, $message);
        if (!$isSilenced) {
            return $stepResult;
        }
        $result = new ExecutedStepResult(
            $stepResult->getSearchResult(),
            new CallResult($call, null, null, self::SILENCED_STEP_MESSAGE_PREFIX . $message),
        );
        $this->failedFeatures->addFailureFeature($feature->getTitle());
        return $result;
    }
}
