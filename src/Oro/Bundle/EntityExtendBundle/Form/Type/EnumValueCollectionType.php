<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumValueCollectionType extends AbstractType
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
        $resolver->setDefaults(
            [
                'ignore_primary_behaviour' => true,
                'type'                     => 'oro_entity_extend_enum_value'
            ]
        );

        $resolver->setNormalizers(
            [
                'can_add_and_delete' => function (Options $options, $value) {
                    return $this->getState($options) > 0 ? false : $value;
                },
                'disabled' => function (Options $options, $value) {
                    return $this->getState($options) === 2 ? true : $value;
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
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['multiple'] = $options['config_id']->getFieldType() === 'multiEnum';
    }

    /**
     * Checks if the form type should be read-only or not
     *
     * @param Options $options
     *
     * @return int 0 - no restrictions, 1 - cannot add/delete, 2 = read only
     */
    protected function getState($options)
    {
        $configId = $options['config_id'];
        if (!($configId instanceof FieldConfigId)) {
            return 0;
        }

        $className = $configId->getClassName();
        if (empty($className)) {
            return 0;
        }

        $fieldName = $configId->getFieldName();

        // check for immutable enums and new field that reuses a public enum
        $enumConfigProvider = $this->configManager->getProvider('enum');
        if ($enumConfigProvider->hasConfig($className, $fieldName)) {
            $enumFieldConfig = $enumConfigProvider->getConfig($className, $fieldName);
            $enumCode        = $enumFieldConfig->get('enum_code');
            if (!empty($enumCode)) {
                // check if a new field reuses existing public enum
                if ($options['config_is_new']) {
                    return true;
                }
                // check immutable
                $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
                if ($enumConfigProvider->hasConfig($enumValueClassName)) {
                    $enumConfig = $enumConfigProvider->getConfig($enumValueClassName);
                    if ($enumConfig->get('immutable')) {
                        return 1;
                    }
                }
            }
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_collection';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_extend_enum_value_collection';
    }
}
