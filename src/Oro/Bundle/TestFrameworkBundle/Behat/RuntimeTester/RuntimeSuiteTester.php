<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\RuntimeTester;

use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Specification\SpecificationIterator;
use Behat\Testwork\Tester\Result\IntegerTestResult;
use Behat\Testwork\Tester\Result\TestResult;
use Behat\Testwork\Tester\Result\TestResults;
use Behat\Testwork\Tester\Result\TestWithSetupResult;
use Behat\Testwork\Tester\Setup\Setup;
use Behat\Testwork\Tester\Setup\SuccessfulSetup;
use Behat\Testwork\Tester\Setup\SuccessfulTeardown;
use Behat\Testwork\Tester\Setup\Teardown;
use Behat\Testwork\Tester\SpecificationTester;
use Behat\Testwork\Tester\SuiteTester;
use Oro\Bundle\TestFrameworkBundle\Behat\Exception\SkippTestExecutionException;

/**
 * A copy of {@see \Behat\Testwork\Tester\Runtime\RuntimeSuiteTester} that provides skipping sub-process execution.
 */
class RuntimeSuiteTester implements SuiteTester
{
    public function __construct(private SpecificationTester $specTester)
    {
    }

    public function setUp(Environment $env, SpecificationIterator $iterator, $skip): Setup
    {
        return new SuccessfulSetup();
    }

    public function test(Environment $env, SpecificationIterator $iterator, $skip = false): TestResult
    {
        $results = [];
        foreach ($iterator as $specification) {
            $setup = $this->specTester->setUp($env, $specification, $skip);
            $localSkip = !$setup->isSuccessful() || $skip;
            try {
                $testResult = $this->specTester->test($env, $specification, $localSkip);
            } catch (SkippTestExecutionException $exception) {
                if ($exception->getCode() > 0) {
                    throw $exception;
                }
                // return empty success test result
                $testResult = new TestResults([new IntegerTestResult(TestResult::PASSED)]);
            }
            $teardown = $this->specTester->tearDown($env, $specification, $localSkip, $testResult);

            $integerResult = new IntegerTestResult($testResult->getResultCode());
            $results[] = new TestWithSetupResult($setup, $integerResult, $teardown);
        }

        return new TestResults($results);
    }

    public function tearDown(Environment $env, SpecificationIterator $iterator, $skip, TestResult $result): Teardown
    {
        return new SuccessfulTeardown();
    }
}
