<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Stub;

use Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface;

class StubScopeCriteriaProvider implements ScopeCriteriaProviderInterface
{
    const STUB_FIELD = 'scopeField';
    const STUB_VALUE = 'stub_value';
    const STUB_CLASS = '\stdClass';

    /**
     * @param array|object $context
     * @return array
     */
    public function getCriteriaByContext($context)
    {
        return [self::STUB_FIELD => $context[self::STUB_FIELD]];
    }

    /**
     * @return array
     */
    public function getCriteriaForCurrentScope()
    {
        return [self::STUB_FIELD => self::STUB_VALUE];
    }

    /**
     * @return string
     */
    public function getCriteriaField()
    {
        return self::STUB_FIELD;
    }

    /**
     * {@inheritdoc}
     */
    public function getCriteriaValueType()
    {
        return self::STUB_CLASS;
    }
}
