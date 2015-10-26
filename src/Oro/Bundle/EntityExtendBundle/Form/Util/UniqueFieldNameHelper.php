<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Util;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class UniqueFieldNameHelper
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * @param ConfigProvider $extendConfigProvider
     */
    public function __construct(ConfigProvider $extendConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;
    }

    /**
     * Checks if we can restore field(unremove) and setter/getter methods can be generated.
     * Characters `_` and `-` are removed from names of methods and as result
     * e.g for names `id` and `i_d` they are identical.
     *
     * For deleted fields setter/getter methods are not generated.
     *
     * @param FieldConfigModel $field
     *
     * @return bool
     */
    public function isFieldCanRestore(FieldConfigModel $field)
    {
        $configs = $this->extendConfigProvider->getConfigs($field->getEntity()->getClassName(), true);
        foreach ($configs as $config) {
            /** @var FieldConfigId $configId */
            $configId  = $config->getId();
            $isDeleted = $config->is('is_deleted') || $config->is('state', ExtendScope::STATE_DELETE);
            $fieldName = $configId->getFieldName();

            // Skip current field.
            if ($field->getFieldName() === $fieldName) {
                continue;
            } elseif (strtolower(Inflector::classify($field->getFieldName())) ===
                      strtolower(Inflector::classify($fieldName))
                      && !$isDeleted
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if field name is uniqueness and setter/getter methods can be generated.
     * Characters `_` and `-` are removed from names of methods and as result
     * e.g for names `id` and `i_d` they are identical.
     *
     * For deleted fields setter/getter methods are not generated.
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    public function isFieldNameUnique($className, $fieldName)
    {
        $configs = $this->extendConfigProvider->getConfigs($className, true);
        foreach ($configs as $config) {
            /** @var FieldConfigId $configId */
            $configId  = $config->getId();
            $isDeleted = $config->is('is_deleted') || $config->is('state', ExtendScope::STATE_DELETE);
            $name      = $configId->getFieldName();

            if ($isDeleted) {
                if (strtolower($fieldName) === strtolower($name)) {
                    return false;
                }
                continue;
            }

            if (strtolower(Inflector::classify($fieldName)) === strtolower(Inflector::classify($name))) {
                return false;
            }
        }

        return true;
    }
}
