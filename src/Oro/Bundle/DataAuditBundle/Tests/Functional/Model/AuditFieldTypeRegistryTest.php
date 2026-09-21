<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Model;

use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * Makes sure the registry declares the type of every auditable field.
 *
 * ChangeSetToAuditFieldsConverter skips a field of an undeclared type without any error, so each
 * type must be in the registry, if only as not auditable.
 */
class AuditFieldTypeRegistryTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testEveryAuditableFieldTypeIsDeclaredInRegistry(): void
    {
        /** @var ConfigManager $configManager */
        $configManager = self::getContainer()->get('oro_entity_config.config_manager');
        /** @var AuditConfigProvider $auditConfigProvider */
        $auditConfigProvider = self::getContainer()->get('oro_dataaudit.audit_config_provider');

        // The application audits the hidden entities and the hidden fields also. Thus include them.
        $withHidden = true;
        $scope = AuditConfigProvider::DATA_AUDIT_SCOPE;

        $violations = [];
        foreach ($configManager->getConfigs($scope, null, $withHidden) as $entityConfig) {
            $className = $entityConfig->getId()->getClassName();
            if (!$auditConfigProvider->isAuditableEntity($className)) {
                continue;
            }

            foreach ($configManager->getConfigs($scope, $className, $withHidden) as $fieldConfig) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();
                $fieldName = $fieldConfigId->getFieldName();
                if (!$auditConfigProvider->isAuditableField($className, $fieldName)) {
                    continue;
                }

                $fieldType = $fieldConfigId->getFieldType();
                if (!AuditFieldTypeRegistry::isType($fieldType)) {
                    $violations[] = sprintf('%s::%s (%s)', $className, $fieldName, $fieldType);
                }
            }
        }

        self::assertSame(
            [],
            $violations,
            'Changes of an auditable field whose type is not declared in AuditFieldTypeRegistry are never audited.'
        );
    }
}
