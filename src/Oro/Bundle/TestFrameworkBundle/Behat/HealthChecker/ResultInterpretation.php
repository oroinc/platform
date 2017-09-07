<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

use Behat\Testwork\Tester\Result\Interpretation\ResultInterpretation as ResultInterpretationInterface;
use Behat\Testwork\Tester\Result\TestResult;

class ResultInterpretation implements ResultInterpretationInterface
{
    /**
     * @var HealthCheckerInterface[]
     */
    protected $healthCheckers = [];

    /**
     * @param HealthCheckerInterface[] $healthCheckers
     */
    public function __construct(array $healthCheckers)
    {
        $this->healthCheckers = $healthCheckers;
    }

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
