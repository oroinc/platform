<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityBundle\Provider\AbstractEntityClassNameProvider;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumEntityClassNameProvider extends AbstractEntityClassNameProvider implements EntityClassNameProviderInterface
{
    /** @var array [enumCode => [class name, field name], ...] */
    private $enumCodesMap;

    /**
     * {@inheritdoc}
     */
    public function getEntityClassName($entityClass)
    {
        return $this->getEnumName($entityClass);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassPluralName($entityClass)
    {
        return $this->getEnumName($entityClass, true);
    }

    /**
     * @param string $entityClass
     * @param bool   $isPlural
     *
     * @return string|null
     */
    protected function getEnumName($entityClass, $isPlural = false)
    {
        if (!$this->configManager->hasConfig($entityClass)) {
            return null;
        }

        $config = $this->configManager->getEntityConfig('enum', $entityClass);
        if ($config->is('public')) {
            return null;
        }

        $enumCode = $config->get('code');
        $enumCodesMap = $this->getEnumCodesMap();
        if (!isset($enumCodesMap[$enumCode])) {
            return null;
        }

        list($className, $fieldName) = $enumCodesMap[$enumCode];

        return $this->getFieldName($className, $fieldName, $isPlural);
    }

    /**
     * @return string[]
     */
    protected function getEnumCodesMap()
    {
        if (null === $this->enumCodesMap) {
            $this->enumCodesMap = [];
            $entityConfigs = $this->configManager->getConfigs('extend', null, true);
            foreach ($entityConfigs as $entityConfig) {
                $className = $entityConfig->getId()->getClassName();
                // skip not extendable, enum and not accessible entities
                if (!$entityConfig->is('is_extend')
                    || $entityConfig->is('inherit', ExtendHelper::BASE_ENUM_VALUE_CLASS)
                    || !ExtendHelper::isEntityAccessible($entityConfig)
                ) {
                    continue;
                }
                // skip dictionaries
                $groups = $this->configManager->getEntityConfig('grouping', $className)->get('groups');
                if (!empty($groups) && in_array(GroupingScope::GROUP_DICTIONARY, $groups, true)) {
                    continue;
                }

                $fieldConfigs = $this->configManager->getConfigs('enum', $className);
                foreach ($fieldConfigs as $fieldConfig) {
                    $enumCode = $fieldConfig->get('enum_code');
                    if (!$enumCode) {
                        continue;
                    }
                    $fieldName = $fieldConfig->getId()->getFieldName();
                    $extendFieldConfig = $this->configManager->getFieldConfig('extend', $className, $fieldName);
                    if (!ExtendHelper::isFieldAccessible($extendFieldConfig)) {
                        continue;
                    }

                    $this->enumCodesMap[$enumCode] = [$className, $fieldName];
                }
            }
        }

        return $this->enumCodesMap;
    }
}
