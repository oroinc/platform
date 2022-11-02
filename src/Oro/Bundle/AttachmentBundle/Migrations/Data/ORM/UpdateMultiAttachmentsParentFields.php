<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Helper\FieldConfigHelper;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Set `parentEntityClass`, `parentEntityId` and `parentEntityFieldName` fields
 * for the all Oro\Bundle\AttachmentBundle\Entity\File entities based on the entities which have relations to them
 * through Oro\Bundle\AttachmentBundle\Entity\FileItem entity relations.
 */
class UpdateMultiAttachmentsParentFields extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        if (!$manager instanceof EntityManager) {
            return;
        }

        $connection = $manager->getConnection();

        $queries = $this->getQueries($manager, $connection->getDatabasePlatform() instanceof MySqlPlatform);
        foreach ($queries as $query) {
            $connection->executeStatement($query);
        }
    }

    private function getQueries(EntityManager $manager, bool $isMySql): array
    {
        $metadataFactory = $manager->getMetadataFactory();
        $metadata = $metadataFactory->getMetadataFor(FileItem::class);
        $configManager = $this->container->get('oro_entity_config.config_manager');

        $queries = [];
        foreach ($metadata->associationMappings as $fieldName => $association) {
            if (!isset($association['inversedBy'])) {
                continue;
            }

            $model = $configManager->getConfigFieldModel($association['targetEntity'], $association['inversedBy']);
            $configId = $configManager->getConfigIdByModel($model, 'attachment');
            if (!$model || !FieldConfigHelper::isMultiField($configId)) {
                continue;
            }

            $fields = implode(
                ' AND ',
                array_map(
                    static function (array $field) {
                        return sprintf('e.%s = afi.%s', $field['referencedColumnName'], $field['name']);
                    },
                    $association['joinColumns']
                )
            );

            $entityMetadata = $metadataFactory->getMetadataFor($association['targetEntity']);

            if ($isMySql) {
                $queries[] = sprintf(
                    'UPDATE oro_attachment_file af ' .
                    'INNER JOIN oro_attachment_file_item afi ON afi.file_id = af.id ' .
                    'INNER JOIN %s e ON %s ' .
                    "SET af.parent_entity_class = '%s', af.parent_entity_field_name = '%s', " .
                    'af.parent_entity_id = e.id ' .
                    'WHERE af.parent_entity_id IS NULL;',
                    $entityMetadata->getTableName(),
                    $fields,
                    \addslashes($association['targetEntity']),
                    $association['inversedBy']
                );
            } else {
                $queries[] = sprintf(
                    'UPDATE oro_attachment_file af ' .
                    "SET parent_entity_class = '%s', parent_entity_field_name = '%s', parent_entity_id = e.id " .
                    'FROM oro_attachment_file_item afi ' .
                    'INNER JOIN %s e ON %s ' .
                    'WHERE af.parent_entity_id IS NULL and afi.file_id = af.id;',
                    $association['targetEntity'],
                    $association['inversedBy'],
                    $entityMetadata->getTableName(),
                    $fields
                );
            }
        }

        return $queries;
    }
}
