<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Translation\Translator;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class EntityFieldProvider
{
    /**
     * @var ConfigProvider
     */
    protected $entityConfigProvider;

    /**
     * @var ConfigProvider
     */
    protected $extendConfigProvider;

    /**
     * @var EntityClassResolver
     */
    protected $entityClassResolver;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var EntityProvider
     */
    protected $entityProvider;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * Constructor
     *
     * @param ConfigProvider $entityConfigProvider
     * @param ConfigProvider $extendConfigProvider
     * @param EntityClassResolver $entityClassResolver
     * @param ManagerRegistry $doctrine
     * @param EntityProvider $entityProvider
     * @param Translator $translator
     */
    public function __construct(
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider,
        EntityClassResolver $entityClassResolver,
        ManagerRegistry $doctrine,
        EntityProvider $entityProvider,
        Translator $translator
    ) {
        $this->entityConfigProvider = $entityConfigProvider;
        $this->extendConfigProvider = $extendConfigProvider;
        $this->entityClassResolver  = $entityClassResolver;
        $this->doctrine             = $doctrine;
        $this->entityProvider       = $entityProvider;
        $this->translator           = $translator;
    }

    /**
     * Returns fields for the given entity
     *
     * @param string $entityName           Entity name. Can be full class name or short form: Bundle:Entity.
     * @param bool $withRelations          Indicates whether fields of related entities should be returned as well.
     * @param bool $withEntityDetails      Indicates whether details of related entity should be returned as well.
     * @param int $deepLevel               The maximum deep level of related entities.
     * @param bool $lastDeepLevelRelations The maximum deep level of related entities.
     * @param bool $translate              Flag means that label, plural label should be translated
     * @return array of fields sorted by field label (relations follows fields)
     *                                       .       'name'          - field name
     *                                       .       'type'          - field type
     *                                       .       'label'         - field label
     *                                       If a field is an identifier (primary key in terms of a database)
     *                                       .       'identifier'    - true for an identifier field
     *                                       If a field represents a relation and $withRelations = true
     *                                       the following attributes are added:
     *                                       .       'relation_type'       - relation type
     *                                       .       'related_entity_name' - entity full class name
     *                                       If a field represents a relation and $withEntityDetails = true
     *                                       the following attributes are added:
     *                                       .       'related_entity_label'        - entity label
     *                                       .       'related_entity_plural_label' - entity plural label
     *                                       .       'related_entity_icon'         - an icon associated with an entity
     *                                       If a field represents a relation and $deepLevel > 0
     *                                       the related entity fields are added:
     *                                       .       'related_entity_fields'       - array of fields
     */
    public function getFields(
        $entityName,
        $withRelations = false,
        $withEntityDetails = false,
        $deepLevel = 0,
        $lastDeepLevelRelations = false,
        $translate = true
    ) {
        $result    = array();
        $className = $this->entityClassResolver->getEntityClass($entityName);
        $em        = $this->getManagerForClass($className);
        $this->addFields($result, $className, $em, $translate);
        if ($withRelations) {
            $this->addRelations(
                $result,
                $className,
                $em,
                $withEntityDetails,
                $deepLevel - 1,
                $lastDeepLevelRelations,
                $translate
            );
        }
        $this->sortFields($result);

        return $result;
    }

    /**
     * Adds entity fields to $result
     *
     * @param array $result
     * @param string $className
     * @param EntityManager $em
     * @param bool $translate
     */
    protected function addFields(array &$result, $className, EntityManager $em, $translate)
    {
        // only configurable entities are supported
        if ($this->entityConfigProvider->hasConfig($className)) {
            $metadata = $em->getClassMetadata($className);

            foreach ($metadata->getFieldNames() as $fieldName) {
                $extendFieldName = null;
                if (strpos($fieldName, ExtendConfigDumper::FIELD_PREFIX) === 0) {
                    $guessedExtendFieldName = substr($fieldName, strlen(ExtendConfigDumper::FIELD_PREFIX));
                    if ($this->extendConfigProvider->getConfig($className, $guessedExtendFieldName)->is('is_extend')) {
                        $extendFieldName = $guessedExtendFieldName;
                    }
                }

                $this->addField(
                    $result,
                    $fieldName,
                    $metadata->getTypeOfField($fieldName),
                    $this->getFieldLabel($className, $extendFieldName ? $extendFieldName : $fieldName),
                    $metadata->isIdentifier($fieldName),
                    $translate
                );
            }
        }
    }

    /**
     * Adds a field to $result
     *
     * @param array $result
     * @param string $name
     * @param string $type
     * @param string $label
     * @param bool $isIdentifier
     * @param bool $translate
     */
    protected function addField(array &$result, $name, $type, $label, $isIdentifier, $translate)
    {
         $field = array(
            'name'  => $name,
            'type'  => $type,
            'label' => $translate ? $this->translator->trans($label) : $label
        );
        if ($isIdentifier) {
            $field['identifier'] = true;
        }
        $result[] = $field;
    }

    /**
     * Adds entity relations to $result
     *
     * @param array         $result
     * @param string        $className
     * @param EntityManager $em
     * @param bool          $withEntityDetails
     * @param int           $relationDeepLevel
     * @param bool          $lastDeepLevelRelations
     * @param bool          $translate
     */
    protected function addRelations(
        array &$result,
        $className,
        EntityManager $em,
        $withEntityDetails,
        $relationDeepLevel,
        $lastDeepLevelRelations,
        $translate
    ) {
        // only configurable entities are supported
        if ($this->entityConfigProvider->hasConfig($className)) {
            $metadata = $em->getClassMetadata($className);
            foreach ($metadata->getAssociationNames() as $associationName) {
                $targetClassName = $metadata->getAssociationTargetClass($associationName);
                if ($this->entityConfigProvider->hasConfig($targetClassName)) {
                    $targetFieldName = $metadata->getAssociationMappedByTargetField($associationName);
                    $targetMetadata  = $em->getClassMetadata($targetClassName);

                    $fieldLabel = $this->getFieldLabel($className, $associationName);
                    $relationData = array(
                        'name' => $associationName,
                        'type' => $targetMetadata->getTypeOfField($targetFieldName),
                        'label' => $translate ? $this->translator->trans($fieldLabel) : $fieldLabel,
                        'relation_type' => $this->getRelationType($className, $associationName),
                        'related_entity_name' => $targetClassName
                    );
                    $this->addRelation(
                        $result,
                        $relationData,
                        $withEntityDetails,
                        $relationDeepLevel,
                        $lastDeepLevelRelations,
                        $translate
                    );
                }
            }
        }
    }

    /**
     * Adds a relation to $result
     *
     * @param array $result
     * @param array $relation
     * @param bool   $withEntityDetails
     * @param int    $relationDeepLevel
     * @param bool   $lastDeepLevelRelations
     * @param bool   $translate
     */
    protected function addRelation(
        array &$result,
        array $relation,
        $withEntityDetails,
        $relationDeepLevel,
        $lastDeepLevelRelations,
        $translate
    ) {
        $name = $relation['name'];
        $relatedEntityName = $relation['related_entity_name'];
        if ($withEntityDetails) {
            $entity = $this->entityProvider->getEntity($relatedEntityName, $translate);
            foreach ($entity as $key => $val) {
                if (!in_array($key, ['name'])) {
                    $relation['related_entity_' . $key] = $val;
                }
            }
        }
        if ($relationDeepLevel >= 0) {
            // set some exceptions
            // todo: we need to find more proper way to do this
            if ($relationDeepLevel > 0 && ($name === 'owner' || $name === 'createdBy' || $name === 'updatedBy')) {
                $relationDeepLevel = 0;
            }
            $relation['related_entity_fields'] =
                $this->getFields(
                    $relatedEntityName,
                    $withEntityDetails && ($relationDeepLevel > 0 || $lastDeepLevelRelations),
                    $withEntityDetails,
                    $relationDeepLevel,
                    $lastDeepLevelRelations,
                    $translate
                );
        }

        $result[] = $relation;
    }

    /**
     * Gets doctrine entity manager for the given class
     *
     * @param string $className
     * @return EntityManager
     * @throws InvalidEntityException
     */
    protected function getManagerForClass($className)
    {
        $manager = null;
        try {
            $manager = $this->doctrine->getManagerForClass($className);
        } catch (\ReflectionException $ex) {
            // ignore not found exception
        }
        if (!$manager) {
            throw new InvalidEntityException(sprintf('The "%s" entity was not found.', $className));
        }

        return $manager;
    }

    /**
     * Gets a field label
     *
     * @param string $className
     * @param string $fieldName
     * @return string
     */
    protected function getFieldLabel($className, $fieldName)
    {
        if ($this->entityConfigProvider->hasConfig($className, $fieldName)) {
            return $this->entityConfigProvider->getConfig($className, $fieldName)->get('label');
        }

        return $fieldName;
    }

    /**
     * Gets a relation type
     *
     * @param string $className
     * @param string $fieldName
     * @return string
     */
    protected function getRelationType($className, $fieldName)
    {
        if ($this->entityConfigProvider->hasConfig($className, $fieldName)) {
            /** @var FieldConfigId $configId */
            $configId = $this->entityConfigProvider->getConfig($className, $fieldName)->getId();
            return $configId->getFieldType();
        }

        return '';
    }

    /**
     * Sorts fields by its label (relations follows fields)
     *
     * @param array $fields
     */
    protected function sortFields(array &$fields)
    {
        usort(
            $fields,
            function ($a, $b) {
                if (isset($a['related_entity_name']) !== isset($b['related_entity_name'])) {
                    if (isset($a['related_entity_name'])) {
                        return 1;
                    }
                    if (isset($b['related_entity_name'])) {
                        return -1;
                    }
                }

                return strcasecmp($a['label'], $b['label']);
            }
        );
    }
}
