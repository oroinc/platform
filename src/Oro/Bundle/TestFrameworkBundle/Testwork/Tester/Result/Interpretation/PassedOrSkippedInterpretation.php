<?php

namespace Oro\Bundle\TestFrameworkBundle\Testwork\Tester\Result\Interpretation;

use Behat\Testwork\Tester\Result\Interpretation\ResultInterpretation;
use Behat\Testwork\Tester\Result\TestResult;

/**
 * Interprets passed or skipped test results as passed.
 */
class PassedOrSkippedInterpretation implements ResultInterpretation
{
    #[\Override]
    public function isFailure(TestResult $result)
    {
        return $result->getResultCode() > TestResult::SKIPPED;
    }
}
