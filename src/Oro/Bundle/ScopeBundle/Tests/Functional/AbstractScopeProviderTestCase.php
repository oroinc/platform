<?php

namespace Oro\Bundle\ScopeBundle\Tests\Functional;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\PropertyAccess\PropertyAccessor;

class AbstractScopeProviderTestCase extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    /**
     * @param string $scopeProviderCriteriaField
     * @param array $scopeTypes
     */
    protected static function assertProviderRegisteredWithScopeTypes($scopeProviderCriteriaField, array $scopeTypes)
    {
        $scopeManager = self::getContainer()->get('oro_scope.scope_manager');

        foreach ($scopeTypes as $scopeType) {
            $scope = self::createScope($scopeProviderCriteriaField, 'value');
            //if provider would be loaded by certain scope type - criteria would be filled with proper value from scope
            $criteria = $scopeManager->getCriteriaByScope($scope, $scopeType);
            self::assertArraySubset(
                [$scopeProviderCriteriaField => 'value'],
                $criteria->toArray(),
                'Criteria field from correct provider is filled by scope value.'
            );
        }
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return Scope
     */
    private static function createScope($field, $value)
    {
        $scope = new StubScope();

        $propertyAccessor = new PropertyAccessor();
        $propertyAccessor->setValue($scope, $field, $value);

        return $scope;
    }
}
