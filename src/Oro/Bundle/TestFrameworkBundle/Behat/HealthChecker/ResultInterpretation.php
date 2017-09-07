<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

use Behat\Testwork\Tester\Result\Interpretation\ResultInterpretation as ResultInterpretationInterface;
use Behat\Testwork\Tester\Result\TestResult;

class ResultInterpretation implements ResultInterpretationInterface, HealthCheckerAwareInterface
{
    use HealthCheckerAwareTrait;

    /**
     * {@inheritdoc}
     */
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
