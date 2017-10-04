<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class AttributeConfigurationProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return string
     */
    public function getAttributeLabel(FieldConfigModel $attribute)
    {
        return $this->getConfig($attribute, 'entity')->get('label', false, $attribute->getFieldName());
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isAttributeActive(FieldConfigModel $attribute)
    {
        return $this->getConfig($attribute, 'extend')
            ->in('state', [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]);
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isAttributeSearchable(FieldConfigModel $attribute)
    {
        return $this->getConfig($attribute)->is('searchable');
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isAttributeFilterable(FieldConfigModel $attribute)
    {
        return $this->getConfig($attribute)->is('filterable');
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isAttributeFilterByExactValue(FieldConfigModel $attribute)
    {
        return $this->getConfig($attribute)->is('filter_by', 'exact_value');
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isAttributeSortable(FieldConfigModel $attribute)
    {
        return $this->getConfig($attribute)->is('sortable');
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isAttributeVisible(FieldConfigModel $attribute)
    {
        return $this->getConfig($attribute)->is('visible');
    }

    /**
     * @param FieldConfigModel $attribute
     * @param string $scope
     *
     * @return ConfigInterface
     */
    protected function getConfig(FieldConfigModel $attribute, $scope = 'attribute')
    {
        $className = $attribute->getEntity()->getClassName();
        $fieldName = $attribute->getFieldName();

        return $this->configManager->getProvider($scope)->getConfig($className, $fieldName);
    }
}
