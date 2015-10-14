<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata as ValidatorMetadata;

class InlineEditingExtension extends AbstractExtension
{
    /**
     * @var OroEntityManager
     */
    protected $entityManager;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @param OroEntityManager   $entityManager
     * @param SecurityFacade     $securityFacade
     * @param AclHelper          $aclHelper
     * @param ValidatorInterface $validator
     */
    public function __construct(
        OroEntityManager $entityManager,
        SecurityFacade $securityFacade,
        AclHelper $aclHelper,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->securityFacade = $securityFacade;
        $this->aclHelper = $aclHelper;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->offsetGetByPath(Configuration::ENABLED_CONFIG_PATH);
    }

    /**
     * Validate configs nad fill default values
     *
     * @param DatagridConfiguration $config
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $configItems    = $config->offsetGetOr(Configuration::BASE_CONFIG_KEY, []);
        $configuration   = new Configuration(Configuration::BASE_CONFIG_KEY);
        $isGranted = $this->securityFacade->isGranted('EDIT', 'entity:' . $configItems['entity_name']);

        $normalizedConfigItems = $this->validateConfiguration(
            $configuration,
            [Configuration::BASE_CONFIG_KEY => $configItems]
        );

        if (!$isGranted) {
            $normalizedConfigItems[Configuration::CONFIG_KEY_ENABLE] = false;
        }

        // replace config values by normalized, extra keys passed directly
        $resultConfigItems = array_replace_recursive($configItems, $normalizedConfigItems);
        if (is_null($resultConfigItems['save_api_accessor']['default_route_parameters']['className'])) {
            $resultConfigItems['save_api_accessor']['default_route_parameters']['className'] =
                str_replace('\\', '_', $configItems['entity_name']);
        }
        $config->offsetSet(Configuration::BASE_CONFIG_KEY, $resultConfigItems);

        //add inline editing where it is possible
        if ($isGranted) {
            $columns = $config->offsetGetOr(FormatterConfiguration::COLUMNS_KEY, []);

            $blackList = $configuration->getBlackList();
            $columns = $this->guessInlineEditingForColumns($columns, $configItems['entity_name'], $blackList);

            $config->offsetSet(FormatterConfiguration::COLUMNS_KEY, $columns);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $data->offsetSet(
            Configuration::BASE_CONFIG_KEY,
            $config->offsetGetOr(Configuration::BASE_CONFIG_KEY, [])
        );
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

    /**
     * @param array  $columns
     * @param string $entityName
     * @param array  $blackList
     *
     * @return mixed
     */
    protected function guessInlineEditingForColumns($columns, $entityName, $blackList)
    {
        $metadata = $this->entityManager->getClassMetadata($entityName);
        /** @var ValidatorMetadata $validatorMetadata */
        $validatorMetadata = $this->validator->getMetadataFor($entityName);

        foreach ($columns as $columnName => &$column) {
            $newColumn = [];
            if ($metadata->hasField($columnName)
                && !in_array($columnName, $blackList)
                && !$metadata->hasAssociation($columnName)
            ) {
                $newColumn[Configuration::BASE_CONFIG_KEY] = ['enable' => true];
                if ($validatorMetadata->hasPropertyMetadata($columnName)) {
                    $newColumn[Configuration::BASE_CONFIG_KEY]['validation_rules'] =
                        $this->getValidationRules($validatorMetadata, $columnName);
                }
            } elseif ($metadata->hasAssociation($columnName)) {
                $mapping = $metadata->getAssociationMapping($columnName);
                if ($mapping['type'] === ClassMetadata::MANY_TO_ONE) {
                    $targetEntity = $metadata->getAssociationTargetClass($columnName);

                    $targetEntityMetadata = $this->entityManager->getClassMetadata($targetEntity);
                    if (isset($newColumn[Configuration::BASE_CONFIG_KEY]['view_options']['value_field_name'])) {
                        $labelField = $newColumn[Configuration::BASE_CONFIG_KEY]['view_options']['value_field_name'];
                    } else {
                        $labelField = $this->guessLabelField($targetEntityMetadata, $columnName);
                    }

                    $newColumn[Configuration::BASE_CONFIG_KEY] = ['enable' => true];
                    if ($validatorMetadata->hasPropertyMetadata($columnName)) {
                        $newColumn[Configuration::BASE_CONFIG_KEY]['validation_rules'] =
                            $this->getValidationRules($validatorMetadata, $columnName);
                    }
                    $newColumn[PropertyInterface::FRONTEND_TYPE_KEY] = 'select';
                    $keyField = $targetEntityMetadata->getSingleIdentifierFieldName();
                    $newColumn['choices'] = $this->getChoices($targetEntity, $keyField, $labelField);
                }
            }

            $column = array_replace_recursive($newColumn, $column);
        }

        return $columns;
    }

    /**
     * @param ValidatorMetadata $validatorMetadata
     * @param string            $columnName
     * @return array
     */
    protected function getValidationRules($validatorMetadata, $columnName)
    {
        $metadata = $validatorMetadata->getPropertyMetadata($columnName);
        $metadata = is_array($metadata) && isset($metadata[0]) ? $metadata[0] : $metadata;

        $rules = [];
        foreach ($metadata->getConstraints() as $constraint) {
            $reflectionClass = new \ReflectionClass($constraint);
            $rules[$reflectionClass->getShortName()] = (array)$constraint;
        }

        return $rules;
    }
}
