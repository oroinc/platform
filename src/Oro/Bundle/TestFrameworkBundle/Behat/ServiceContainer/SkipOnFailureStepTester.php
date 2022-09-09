<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer;

use Behat\Behat\Tester\Result\StepResult;
use Behat\Behat\Tester\StepTester;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Tester\Setup\Setup;
use Behat\Testwork\Tester\Setup\Teardown;
use Oro\Bundle\TestFrameworkBundle\Behat\Storage\FailedFeatures;

/**
 * Tester executing step tests in the runtime with skip all feature steps if some step failed.
 */
class SkipOnFailureStepTester implements StepTester
{
    public function __construct(protected StepTester $baseTester, protected FailedFeatures $failedFeatures)
    {
    }

    public function setUp(Environment $env, FeatureNode $feature, StepNode $step, $skip): Setup
    {
        return $this->baseTester->setUp($env, $feature, $step, $skip);
    }

    public function test(Environment $env, FeatureNode $feature, StepNode $step, $skip): StepResult
    {
        if ($this->failedFeatures->isFailureFeature($this->getFeatureHelperId($feature))) {
            $skip = true;
        }

        return $this->baseTester->test($env, $feature, $step, $skip);
    }

    public function tearDown(
        Environment $env,
        FeatureNode $feature,
        StepNode $step,
        $skip,
        StepResult $result
    ): Teardown {
        if (!$result->isPassed()) {
            $this->failedFeatures->addFailureFeature($this->getFeatureHelperId($feature));
            $skip = true;
        }

        return $this->baseTester->tearDown($env, $feature, $step, $skip, $result);
    }

    private function getFeatureHelperId(FeatureNode $feature): string
    {
        return $feature->getFile() . $feature->getTitle();
    }
}
