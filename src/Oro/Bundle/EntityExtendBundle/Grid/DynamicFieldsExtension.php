<?php

namespace Oro\Bundle\EntityExtendBundle\Grid;

use Oro\Component\PhpUtils\ArrayUtil;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridGuesser;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class DynamicFieldsExtension extends AbstractFieldsExtension
{
    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * @param ConfigManager       $configManager
     * @param EntityClassResolver $entityClassResolver
     * @param DatagridGuesser     $datagridGuesser
     * @param FeatureChecker      $featureChecker
     */
    public function __construct(
        ConfigManager $configManager,
        EntityClassResolver $entityClassResolver,
        DatagridGuesser $datagridGuesser,
        FeatureChecker $featureChecker
    ) {
        parent::__construct($configManager, $entityClassResolver, $datagridGuesser);

        $this->featureChecker = $featureChecker;
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
    protected function getEntityName(DatagridConfiguration $config)
    {
        return $config->getExtendedEntityClassName();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFields(DatagridConfiguration $config)
    {
        $entityClassName = $this->entityClassResolver->getEntityClass($this->getEntityName($config));
        if (!$this->configManager->hasConfig($entityClassName)) {
            return [];
        }

        $entityConfigProvider   = $this->configManager->getProvider('entity');
        $extendConfigProvider   = $this->configManager->getProvider('extend');
        $viewConfigProvider     = $this->configManager->getProvider('view');
        $datagridConfigProvider = $this->configManager->getProvider('datagrid');

        $fields = [];
        $fieldIds = $entityConfigProvider->getIds($entityClassName);
        /** @var FieldConfigId $fieldId */
        foreach ($fieldIds as $fieldId) {
            $extendConfig = $extendConfigProvider->getConfigById($fieldId);
            $fieldConfig = $datagridConfigProvider->getConfigById($fieldId);

            if ($this->isApplicableField($extendConfig, $fieldConfig)) {
                $viewConfig = $viewConfigProvider->getConfig($entityClassName, $fieldId->getFieldName());
                $fields[] = [
                    'id'       => $fieldId,
                    'priority' => $viewConfig->get('priority', false, 0)
                ];
            }
        }

        ArrayUtil::sortBy($fields, true);

        return array_map(
            function ($field) {
                return $field['id'];
            },
            $fields
        );
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
     * @param ConfigInterface $extendConfig
     * @param ConfigInterface $fieldConfig
     *
     * @return bool
     */
    protected function isApplicableField(ConfigInterface $extendConfig, ConfigInterface $fieldConfig)
    {
        if ($extendConfig->has('target_entity')
            && !$this->featureChecker->isResourceEnabled($extendConfig->get('target_entity'), 'entities')
        ) {
            return false;
        }

        return $extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
                && ExtendHelper::isFieldAccessible($extendConfig)
                && $fieldConfig->is('is_visible');
    }
}
