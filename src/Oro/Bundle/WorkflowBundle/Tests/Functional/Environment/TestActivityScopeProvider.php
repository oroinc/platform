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
    public function getCriteriaField()
    {
        return self::TEST_ACTIVITY;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaValue()
    {
        return $this->currentTestActivity;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaValueType()
    {
        return TestActivity::class;
    }

    /**
     * @param TestActivity|null $currentTestActivity
     */
    public function setCurrentTestActivity(?TestActivity $currentTestActivity)
    {
        $this->currentTestActivity = $currentTestActivity;
    }
}
