<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Oro\Bundle\ScopeBundle\Manager\AbstractScopeCriteriaProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;

class TestActivityScopeProvider extends AbstractScopeCriteriaProvider
{
    const TEST_ACTIVITY = 'test_activity';

    /** @var TestActivity */
    private $currentTestActivity;

    /**
     * {@inheritdoc}
     */
    public function getCriteriaForCurrentScope()
    {
        return [static::TEST_ACTIVITY => $this->currentTestActivity];
    }

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
    public function getCriteriaValueType()
    {
        return TestActivity::class;
    }

    /**
     * @param TestActivity $currentTestActivity
     */
    public function setCurrentTestActivity(TestActivity $currentTestActivity)
    {
        $this->currentTestActivity = $currentTestActivity;
    }
}
