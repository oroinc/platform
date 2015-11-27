<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

/**
 * Class ChoicesGuesser
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions
 */
class ChoicesGuesser implements GuesserInterface
{
    /** Frontend type */
    const SELECT = 'select';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param AclHelper $aclHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper, AclHelper $aclHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function guessColumnOptions($columnName, $entityName, $column)
    {
        $entityManager = $this->doctrineHelper->getEntityManager($entityName);
        $metadata = $entityManager->getClassMetadata($entityName);

        $result = [];
        $isConfigured = isset($column[Configuration::BASE_CONFIG_KEY][Configuration::EDITOR_KEY]);
        $isConfigured = $isConfigured
            || isset($column[Configuration::BASE_CONFIG_KEY][Configuration::AUTOCOMPLETE_API_ACCESSOR_KEY]);
        if (!$isConfigured && $metadata->hasAssociation($columnName)) {
            $mapping = $metadata->getAssociationMapping($columnName);
            if ($mapping['type'] === ClassMetadata::MANY_TO_ONE) {
                $targetEntity = $metadata->getAssociationTargetClass($columnName);

                $targetEntityMetadata = $entityManager->getClassMetadata($targetEntity);
                if (isset($column[Configuration::BASE_CONFIG_KEY]
                    [Configuration::VIEW_OPTIONS_KEY][Configuration::VALUE_FIELD_NAME_KEY])) {
                    $labelField = $column[Configuration::BASE_CONFIG_KEY]
                    [Configuration::VIEW_OPTIONS_KEY][Configuration::VALUE_FIELD_NAME_KEY];
                } else {
                    $labelField = $this->guessLabelField($targetEntityMetadata, $columnName);
                }

                $result[Configuration::BASE_CONFIG_KEY] = [Configuration::CONFIG_ENABLE_KEY => true];
                $result[PropertyInterface::FRONTEND_TYPE_KEY] = self::SELECT;

                $keyField = $targetEntityMetadata->getSingleIdentifierFieldName();
                $result[Configuration::CHOICES_KEY] = $this->getChoices($targetEntity, $keyField, $labelField);
            }
        }

        return $result;
    }

    /**
     * @param ClassMetadata $metadata
     * @param string        $columnName
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function guessLabelField($metadata, $columnName)
    {
        $labelField = '';

        if ($metadata->hasField('label')) {
            $labelField = 'label';
        } elseif ($metadata->hasField('name')) {
            $labelField = 'name';
        } else {
            //get first field with type "string"
            $isStringFieldPresent = false;
            foreach ($metadata->getFieldNames() as $fieldName) {
                if ($metadata->getTypeOfField($fieldName) === "string") {
                    $labelField = $fieldName;
                    $isStringFieldPresent = true;
                    break;
                }
            }

            if (!$isStringFieldPresent) {
                throw new \Exception(
                    "Could not find any field for using as label for 'choices' of '$columnName' column."
                );
            }
        }

        return $labelField;
    }

    /**
     * @param string $entity
     * @param string $keyField
     * @param string $labelField
     *
     * @return array
     */
    protected function getChoices($entity, $keyField, $labelField)
    {
        $entityManager = $this->doctrineHelper->getEntityManager($entity);
        $queryBuilder = $entityManager
            ->getRepository($entity)
            ->createQueryBuilder('e');
        //select only id and label fields
        $queryBuilder->select("e.$keyField, e.$labelField");

        $result = $this->aclHelper->apply($queryBuilder)->getResult();
        $choices = [];
        foreach ($result as $item) {
            $choices[$item[$keyField]] = $item[$labelField];
        }

        return $choices;
    }
}
