<?php

namespace Oro\Bundle\EntityExtendBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Adds extended entity fields to datagrids.
 *
 * Applicable only for ORM datasource.
 * Applicable for datagrids which have "extended_entity_name" configuration option.
 * Does not add attributes which are not present in any attribute family.
 */
class DynamicFieldsExtension extends AbstractFieldsExtension
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var SelectedFieldsProviderInterface */
    private $selectedFieldsProvider;

    /**
     * @param ConfigManager $configManager
     * @param EntityClassResolver $entityClassResolver
     * @param DatagridGuesser $datagridGuesser
     * @param FieldsHelper $fieldsHelper
     * @param DoctrineHelper $doctrineHelper
     * @param SelectedFieldsProviderInterface $selectedFieldsProvider
     */
    public function __construct(
        ConfigManager $configManager,
        EntityClassResolver $entityClassResolver,
        DatagridGuesser $datagridGuesser,
        FieldsHelper $fieldsHelper,
        DoctrineHelper $doctrineHelper,
        SelectedFieldsProviderInterface $selectedFieldsProvider
    ) {
        parent::__construct($configManager, $entityClassResolver, $datagridGuesser, $fieldsHelper);

        $this->doctrineHelper = $doctrineHelper;
        $this->selectedFieldsProvider = $selectedFieldsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        if (!parent::isApplicable($config) || !$config->getExtendedEntityClassName()) {
            return false;
        }

        $entityClassName = $this->entityClassResolver->getEntityClass($this->getEntityName($config));
        /** @var ConfigProvider $extendProvider */
        $extendProvider = $this->configManager->getProvider('extend');
        if (!$extendProvider->hasConfig($entityClassName)) {
            return false;
        }

        $extendConfig = $extendProvider->getConfig($entityClassName);

        return $extendConfig->is('is_extend');
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 300;
    }

    /**
     * {@inheritdoc}
     */
    public function buildExpression(array $fields, DatagridConfiguration $config, $alias)
    {
        if ($this->selectedFieldsProvider) {
            $selectedFields = $this->selectedFieldsProvider->getSelectedFields($config, $this->getParameters());
            $fields = $this->filterRelevant($fields, $selectedFields);
        }

        return parent::buildExpression($fields, $config, $alias);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityName(DatagridConfiguration $config)
    {
        return $config->getExtendedEntityClassName();
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareColumnOptions(FieldConfigId $field, array &$columnOptions)
    {
        parent::prepareColumnOptions($field, $columnOptions);

        if ($this->getFieldConfig('datagrid', $field)->is('show_filter')) {
            $columnOptions[DatagridGuesser::FILTER]['enabled'] = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getFields(DatagridConfiguration $config)
    {
        return $this->excludeDanglingAttributes($config, parent::getFields($config));
    }

    /**
     * Excludes attributes which are not present in any attribute family.
     *
     * @param DatagridConfiguration $config
     * @param FieldConfigId[] $fieldsConfigIds
     *
     * @return array
     */
    private function excludeDanglingAttributes(DatagridConfiguration $config, array $fieldsConfigIds): array
    {
        if (!$this->doctrineHelper) {
            return $fieldsConfigIds;
        }

        if (!$fieldsConfigIds) {
            return [];
        }

        if (!$this->canHaveAttributes($this->getEntityName($config))) {
            // Returns all $fieldsConfigIds because entity cannot have attributes.
            return $fieldsConfigIds;
        }

        $attributesFieldsConfigIds = $this->filterAttributes($fieldsConfigIds);
        if (!$attributesFieldsConfigIds) {
            // Returns all $fieldsConfigIds because there are no attributes which can be excluded.
            return $fieldsConfigIds;
        }

        $attributesFieldsConfigModels = $this->getModelsFromFieldConfigsIds($attributesFieldsConfigIds);
        $familiesIdsByAttributesIds = $this->getAttributeFamilyRepository()
            ->getFamilyIdsForAttributes(array_values($attributesFieldsConfigModels));

        // Excludes attributes which are not present in any attribute family.
        foreach ($attributesFieldsConfigIds as $fieldConfigId) {
            /** @var FieldConfigModel $fieldConfigModel */
            $fieldConfigModel = $attributesFieldsConfigModels[$fieldConfigId->toString()] ?? null;
            if (!$fieldConfigModel) {
                continue;
            }

            if (isset($familiesIdsByAttributesIds[$fieldConfigModel->getId()])) {
                continue;
            }

            unset($fieldsConfigIds[array_search($fieldConfigId, $fieldsConfigIds, false)]);
        }

        return $fieldsConfigIds;
    }

    /**
     * @param string $entityName
     *
     * @return bool
     */
    private function canHaveAttributes(string $entityName): bool
    {
        $entityClassName = $this->entityClassResolver->getEntityClass($entityName);

        return $this->configManager
            ->getEntityConfig('attribute', $entityClassName)
            ->is('has_attributes');
    }

    /**
     * @param FieldConfigId[] $fieldsConfigIds
     *
     * @return array
     */
    private function filterAttributes(array $fieldsConfigIds): array
    {
        return array_filter($fieldsConfigIds, function (FieldConfigId $fieldConfigId) {
            $attributeConfig = $this->getFieldConfig('attribute', $fieldConfigId);

            return $attributeConfig->is('is_attribute');
        });
    }

    /**
     * @param FieldConfigId[] $fieldsConfigIds
     *
     * @return FieldConfigModel[]
     */
    private function getModelsFromFieldConfigsIds(array $fieldsConfigIds): array
    {
        $fieldConfigModels = [];
        foreach ($fieldsConfigIds as $fieldConfigId) {
            $fieldConfigModels[$fieldConfigId->toString()] = $this->configManager
                ->getConfigFieldModel($fieldConfigId->getClassName(), $fieldConfigId->getFieldName());
        }

        return array_filter($fieldConfigModels);
    }

    /**
     * @return AttributeFamilyRepository
     */
    private function getAttributeFamilyRepository(): AttributeFamilyRepository
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(AttributeFamily::class);
    }

    /**
     * @param array $fieldConfigsIds
     * @param array $selectedFields
     *
     * @return array
     */
    private function filterRelevant(array $fieldConfigsIds, array $selectedFields): array
    {
        return array_filter(
            $fieldConfigsIds,
            function (FieldConfigId $fieldConfigId) use ($selectedFields) {
                return \in_array($fieldConfigId->getFieldName(), $selectedFields, false);
            }
        );
    }
}
