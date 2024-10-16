<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Environment;

use Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;

class TestActivityScopeProvider implements ScopeCriteriaProviderInterface
{
    public const TEST_ACTIVITY = 'test_activity';

    /** @var TestActivity */
    private $currentTestActivity;

    /**
     * @return string
     */
    #[\Override]
    public function getCriteriaField()
    {
        return self::TEST_ACTIVITY;
    }

    #[\Override]
    public function getCriteriaValue()
    {
        return $this->currentTestActivity;
    }

    #[\Override]
    public function getCriteriaValueType()
    {
        return TestActivity::class;
    }

    public function setCurrentTestActivity(?TestActivity $currentTestActivity)
    {
        $this->currentTestActivity = $currentTestActivity;
    }
}
