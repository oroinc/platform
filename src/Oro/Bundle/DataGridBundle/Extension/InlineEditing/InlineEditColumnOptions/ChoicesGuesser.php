<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

/**
 * Class ChoicesGuesser
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions
 */
class ChoicesGuesser implements GuesserInterface
{
    /**
     * @var OroEntityManager
     */
    protected $entityManager;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @param OroEntityManager $oroEntityManager
     * @param AclHelper        $aclHelper
     */
    public function __construct(OroEntityManager $oroEntityManager, AclHelper $aclHelper)
    {
        $this->entityManager = $oroEntityManager;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param string $columnName
     * @param string $entityName
     * @param array  $column
     *
     * @return array
     *
     * @throws \Exception
     */
    public function guessColumnOptions($columnName, $entityName, $column)
    {
        $metadata = $this->entityManager->getClassMetadata($entityName);

        $result = [];
        $isConfigured = isset($column[Configuration::BASE_CONFIG_KEY]['editor']);
        $isConfigured = $isConfigured || isset($column[Configuration::BASE_CONFIG_KEY]['autocomplete_api_accessor']);
        if (!$isConfigured && $metadata->hasAssociation($columnName)) {
            $mapping = $metadata->getAssociationMapping($columnName);
            if ($mapping['type'] === ClassMetadata::MANY_TO_ONE) {
                $targetEntity = $metadata->getAssociationTargetClass($columnName);

                $targetEntityMetadata = $this->entityManager->getClassMetadata($targetEntity);
                if (isset($column[Configuration::BASE_CONFIG_KEY]['view_options']['value_field_name'])) {
                    $labelField = $column[Configuration::BASE_CONFIG_KEY]['view_options']['value_field_name'];
                } else {
                    $labelField = $this->guessLabelField($targetEntityMetadata, $columnName);
                }

                $result[Configuration::BASE_CONFIG_KEY] = ['enable' => true];

                $result[PropertyInterface::FRONTEND_TYPE_KEY] = 'select';
                $keyField = $targetEntityMetadata->getSingleIdentifierFieldName();
                $result['choices'] = $this->getChoices($targetEntity, $keyField, $labelField);
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
        $queryBuilder = $this->entityManager
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
