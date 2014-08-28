<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumPublicType extends AbstractType
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
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setNormalizers(
            [
                'disabled'          => function (Options $options, $value) {
                    return $this->isReadOnly($options) ? true : $value;
                },
                'validation_groups' => function (Options $options, $value) {
                    return $options['disabled'] ? false : $value;
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_extend_enum_public';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * Checks if the form type should be read-only or not
     *
     * @param Options $options
     *
     * @return bool
     */
    protected function isReadOnly($options)
    {
        $configId = $options['config_id'];
        if (!($configId instanceof FieldConfigId)) {
            return false;
        }

        $className = $configId->getClassName();
        if (empty($className)) {
            return false;
        }

        $fieldName = $configId->getFieldName();

        // check if a system field
        $extendConfigProvider = $this->configManager->getProvider('extend');
        if ($extendConfigProvider->hasConfig($className, $fieldName)) {
            $extendConfig = $extendConfigProvider->getConfig($className, $fieldName);
            if ($extendConfig->is('owner', ExtendScope::OWNER_SYSTEM)) {
                return true;
            }
        }

        // check if:
        //  - immutable enum
        //  - new field reuses a public enum
        //  - a public enum is reused by other fields
        $enumConfigProvider = $this->configManager->getProvider('enum');
        if ($enumConfigProvider->hasConfig($className, $fieldName)) {
            $enumFieldConfig = $enumConfigProvider->getConfig($className, $fieldName);
            $enumCode        = $enumFieldConfig->get('enum_code');
            if (!empty($enumCode)) {
                // check if a new field reuses public enum
                if ($options['config_is_new']) {
                    return true;
                }
                // check immutable
                $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
                if ($enumConfigProvider->hasConfig($enumValueClassName)) {
                    $enumConfig = $enumConfigProvider->getConfig($enumValueClassName);
                    if ($enumConfig->get('immutable')) {
                        return true;
                    }
                }
                // check if a public enum is reused by other fields
                $entityConfigs = $extendConfigProvider->getConfigs();
                foreach ($entityConfigs as $entityConfig) {
                    $enumFieldConfigs = $enumConfigProvider->getConfigs($entityConfig->getId()->getClassName());
                    foreach ($enumFieldConfigs as $enumFieldConfig) {
                        /** @var FieldConfigId $fieldConfigId */
                        $fieldConfigId = $enumFieldConfig->getId();
                        if (!in_array($fieldConfigId->getFieldType(), ['enum', 'multiEnum'])) {
                            // skip not enum fields
                            continue;
                        }
                        if ($fieldConfigId->getFieldName() === $fieldName
                            && $fieldConfigId->getClassName() === $className
                        ) {
                            // skip current field
                            continue;
                        }
                        $fieldEnumCode = $enumFieldConfig->get('enum_code');
                        if (!empty($fieldEnumCode) && $fieldEnumCode === $enumCode) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
