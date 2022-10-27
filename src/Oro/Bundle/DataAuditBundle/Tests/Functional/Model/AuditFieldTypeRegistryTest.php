<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Model;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class AuditFieldTypeRegistryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testTypesRegistered()
    {
        $doctrineTypes = Type::getTypesMap();
        foreach ($doctrineTypes as $doctrineType => $doctrineTypeClass) {
            AuditFieldTypeRegistry::isType($doctrineType);
        }

        foreach (RelationType::$anyToAnyRelations as $doctrineType) {
            AuditFieldTypeRegistry::isType($doctrineType);
        }

        foreach (RelationType::$toAnyRelations as $doctrineType) {
            AuditFieldTypeRegistry::isType($doctrineType);
        }

        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');

        /** @var FieldConfigModel[] $fields */
        $fields = $doctrineHelper
            ->getEntityManager(FieldConfigModel::class)
            ->getRepository(FieldConfigModel::class)
            ->findAll();

        /** @var ConfigManager $configProvider */
        $configProvider = $this->getContainer()->get('oro_entity_config.config_manager');

        foreach ($fields as $field) {
            AuditFieldTypeRegistry::isType($field->getType());

            $configProvider
                ->getEntityConfig('dataaudit', $field->getEntity()->getClassName())
                ->set('auditable', true);
            $configProvider
                ->getFieldConfig('dataaudit', $field->getEntity()->getClassName(), $field->getFieldName())
                ->set('auditable', true);
            $configProvider->flush();
        }
    }
}
