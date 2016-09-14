<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataAuditBundle\Entity\AuditField;
use Metadata\ClassMetadata as AuditClassMetadata;

class ConvertChangeSetToAuditFieldsService
{
    /**
     * @var GetHumanReadableEntityNameService
     */
    private $getHumanReadableEntityNameService;

    /**
     * @param GetHumanReadableEntityNameService $getHumanReadableEntityNameService
     */
    public function __construct(GetHumanReadableEntityNameService $getHumanReadableEntityNameService)
    {
        $this->getHumanReadableEntityNameService = $getHumanReadableEntityNameService;
    }

    /**
     * @param AuditClassMetadata $entityAuditMeta
     * @param ClassMetadata $entityMeta
     * @param array $changeSet
     *
     * @return AuditField[]
     */
    public function convert(AuditClassMetadata $entityAuditMeta, ClassMetadata $entityMeta, array $changeSet)
    {
        $fields = [];
        foreach ($changeSet as $fieldName => $change) {
            list($old, $new) = $change;

            // is field auditable?
            if (false == isset($entityAuditMeta->propertyMetadata[$fieldName])) {
                continue;
            }

            if ($entityMeta->hasField($fieldName)) {
                $fieldMapping = $entityMeta->getFieldMapping($fieldName);
                $fieldType = $fieldMapping['type'];

                if (in_array($fieldType, ['date', 'datetime']) && $old) {
                    $old = \DateTime::createFromFormat(DATE_ISO8601, $old);
                }

                if (in_array($fieldType, ['date', 'datetime']) && $new) {
                    $new = \DateTime::createFromFormat(DATE_ISO8601, $new);
                }

                $fields[$fieldName] = new AuditField($fieldName, $fieldType, $new, $old);
            } elseif (isset($entityMeta->associationMappings[$fieldName]) &&
                is_array($new) &&
                array_key_exists('inserted', $new) &&
                array_key_exists('deleted', $new)
            ) {
                $fields[$fieldName] = $field = new AuditField($fieldName, 'collection', null, null);
                foreach ($new['inserted'] as $entity) {
                    $field->addEntityAddedToCollection(
                        $entity['entity_class'],
                        $entity['entity_id'],
                        $this->getHumanReadableEntityNameService->getName(
                            $entity['entity_class'],
                            $entity['entity_id']
                        )
                    );
                }

                if ($new['deleted']) {
                    foreach ($new['deleted'] as $entity) {
                        $field->addEntityRemovedFromCollection(
                            $entity['entity_class'],
                            $entity['entity_id'],
                            $this->getHumanReadableEntityNameService->getName(
                                $entity['entity_class'],
                                $entity['entity_id']
                            )
                        );
                    }
                }

                if ($new['changed']) {
                    foreach ($new['changed'] as $entity) {
                        $field->addEntityChangedInCollection(
                            $entity['entity_class'],
                            $entity['entity_id'],
                            $this->getHumanReadableEntityNameService->getName(
                                $entity['entity_class'],
                                $entity['entity_id']
                            )
                        );
                    }
                }

                $field->calculateNewValue();
            } elseif (isset($entityMeta->associationMappings[$fieldName])) {
                $newName = $new ?
                    $this->getHumanReadableEntityNameService->getName($new['entity_class'], $new['entity_id']) :
                    null;

                $oldName = $old ?
                    $this->getHumanReadableEntityNameService->getName($old['entity_class'], $old['entity_id']) :
                    null;

                $fields[$fieldName] = new AuditField($fieldName, 'text', $newName, $oldName);
            } else {
                $fields[$fieldName] = new AuditField(
                    $fieldName,
                    'text',
                    (string) $new,
                    (string) $old
                );
            }
        }

        return $fields;
    }
}
