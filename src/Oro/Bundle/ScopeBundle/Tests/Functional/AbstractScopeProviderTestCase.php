<?php

namespace Oro\Bundle\ScopeBundle\Tests\Functional;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\PropertyAccess\PropertyAccessor;

class AbstractScopeProviderTestCase extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    protected static function assertProviderRegisteredWithScopeTypes(
        string $scopeProviderCriteriaField,
        array $scopeTypes
    ): void {
        $scopeManager = self::getContainer()->get('oro_scope.scope_manager');

        foreach ($scopeTypes as $scopeType) {
            $scope = self::createScope($scopeProviderCriteriaField, 'value');
            //if provider would be loaded by certain scope type - criteria would be filled with proper value from scope
            $criteria = $scopeManager->getCriteriaByScope($scope, $scopeType);

            self::assertEquals(
                'value',
                $criteria->toArray()[$scopeProviderCriteriaField],
                'Criteria field from correct provider is filled by scope value.'
            );
        }
    }

    private static function createScope(string $field, mixed $value): Scope
    {
        $scope = new Scope();
        (new PropertyAccessor())->setValue($scope, $field, $value);

        return $scope;
    }
}
