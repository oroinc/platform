<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityBundle\Provider\AbstractEntityClassNameProvider;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumEntityClassNameProvider extends AbstractEntityClassNameProvider implements EntityClassNameProviderInterface
{
    /** @var string[] */
    private $potentialEnumHolderClassNames;

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
        $enumConfigProvider = $this->configManager->getProvider('enum');
        if (!$enumConfigProvider->hasConfig($entityClass)) {
            return null;
        }

        $config = $enumConfigProvider->getConfig($entityClass);
        if ($config->is('public')) {
            return null;
        }

        $enumCode             = $config->get('code');
        $classNames           = $this->getPotentialEnumHolderClassNames();
        $extendConfigProvider = $this->configManager->getProvider('extend');
        foreach ($classNames as $className) {
            foreach ($enumConfigProvider->getConfigs($className) as $fieldConfig) {
                if ($fieldConfig->is('enum_code', $enumCode)) {
                    $fieldName = $fieldConfig->getId()->getFieldName();
                    if (!$extendConfigProvider->getConfig($className, $fieldName)->is('is_deleted')) {
                        return $this->getFieldName($className, $fieldName, $isPlural);
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    protected function getPotentialEnumHolderClassNames()
    {
        if (null === $this->potentialEnumHolderClassNames) {
            $this->potentialEnumHolderClassNames = array_map(
                function (ConfigInterface $config) {
                    return $config->getId()->getClassName();
                },
                $this->configManager->getProvider('extend')->filter(
                    function (ConfigInterface $config) {
                        return ExtendHelper::isEnumValueEntityAccessible($config);
                    }
                )
            );

            // skip dictionaries
            $groupingConfigProvider              = $this->configManager->getProvider('grouping');
            $this->potentialEnumHolderClassNames = array_filter(
                $this->potentialEnumHolderClassNames,
                function ($className) use ($groupingConfigProvider) {
                    if (!$groupingConfigProvider->hasConfig($className)) {
                        return true;
                    }

                    $config = $groupingConfigProvider->getConfig($className);
                    $groups = $config->get('groups');
                    if (empty($groups)) {
                        return true;
                    }

                    return !in_array(GroupingScope::GROUP_DICTIONARY, $groups, true);
                }
            );
        }

        return $this->potentialEnumHolderClassNames;
    }
}
