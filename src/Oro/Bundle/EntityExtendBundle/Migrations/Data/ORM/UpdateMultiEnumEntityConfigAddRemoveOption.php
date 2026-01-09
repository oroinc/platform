<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Update entity configuration for multi-enum fields, remove schema "addremove" option (relation configuration).
 */
class UpdateMultiEnumEntityConfigAddRemoveOption extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function load(ObjectManager $manager): void
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }
        $this->removeAddRemoveOptionForMultiEnums($manager->getConnection());
    }

    protected function removeAddRemoveOptionForMultiEnums(Connection $connection): void
    {
        $multiEnumFields = $connection->fetchAllAssociative(
            "SELECT ecf.entity_id, ecf.field_name FROM oro_entity_config_field as ecf WHERE ecf.type = 'multiEnum'"
        );
        if (!$multiEnumFields) {
            return;
        }
        foreach ($multiEnumFields as $multiEnumField) {
            $entityConfigData = $connection->fetchAssociative(
                "SELECT ec.data FROM oro_entity_config as ec WHERE ec.id = ?",
                [$multiEnumField['entity_id']]
            );
            $data = $connection->convertToPHPValue($entityConfigData['data'], Types::ARRAY);
            if (!isset($data['extend']['schema']['addremove'][$multiEnumField['field_name']])) {
                continue;
            }
            unset($data['extend']['schema']['addremove'][$multiEnumField['field_name']]);

            $data = $connection->convertToDatabaseValue($data, Types::ARRAY);
            $connection->executeQuery(
                'UPDATE oro_entity_config SET data = ? WHERE id = ?',
                [$data, $multiEnumField['entity_id']]
            );
        }
    }
}
