<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Set `parentEntityClass`, `parentEntityId` and `parentEntityFieldName` fields
 * for the all Oro\Bundle\AttachmentBundle\Entity\File entities based on the entities which have relations to them.
 */
class UpdateAttachmentsParentFields extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
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
        $queries = [];

        foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
            if (!$metadata instanceof ClassMetadataInfo || !$metadata->getTableName()) {
                continue;
            }

            foreach ($metadata->getAssociationMappings() as $association) {
                if (empty($association['isOwningSide']) || !is_a($association['targetEntity'], File::class, true)) {
                    continue;
                }

                $fields = implode(
                    ' AND ',
                    array_map(
                        function (array $field) {
                            return sprintf(
                                'af.%s = e.%s',
                                $field['referencedColumnName'],
                                $field['name']
                            );
                        },
                        $association['joinColumns']
                    )
                );

                if ($isMySql) {
                    $queries[] = sprintf(
                        'UPDATE oro_attachment_file af ' .
                        'INNER JOIN %s e ON %s ' .
                        "SET af.parent_entity_class = '%s', af.parent_entity_field_name = '%s', " .
                        'af.parent_entity_id = e.id WHERE af.parent_entity_id IS NULL;',
                        $metadata->getTableName(),
                        $fields,
                        \addslashes($metadata->getName()),
                        $association['fieldName']
                    );
                } else {
                    $queries[] = sprintf(
                        'UPDATE oro_attachment_file af ' .
                        "SET parent_entity_class = '%s', parent_entity_field_name = '%s', parent_entity_id = e.id " .
                        'FROM %s e ' .
                        'WHERE af.parent_entity_id IS NULL AND %s;',
                        $metadata->getName(),
                        $association['fieldName'],
                        $metadata->getTableName(),
                        $fields
                    );
                }
            }
        }

        return $queries;
    }
}
