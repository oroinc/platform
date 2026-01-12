<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

use Behat\Testwork\Tester\Result\Interpretation\ResultInterpretation as ResultInterpretationInterface;
use Behat\Testwork\Tester\Result\TestResult;

/**
 * Interprets test results based on health checker failures.
 *
 * This class marks test results as failures if any registered health checker reports
 * a failure, allowing health checks to influence the overall test result.
 */
class ResultInterpretation implements ResultInterpretationInterface, HealthCheckerAwareInterface
{
    use HealthCheckerAwareTrait;

    #[\Override]
    public function isFailure(TestResult $result)
    {
        foreach ($this->healthCheckers as $healthChecker) {
            if ($healthChecker->isFailure()) {
                return 1;
            }
        }

        return 0;
    }
}
