<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
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
                    return $this->isImmutable($options)
                        ? false
                        : $value;
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace(
            $view->vars,
            [
                'multiple' => $options['config_id']->getFieldType() === 'multiEnum'
            ]
        );
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

    /**
     * @param array|Options $options
     *
     * @return bool
     */
    protected function isImmutable($options)
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

        $enumConfigProvider = $this->configManager->getProvider('enum');
        if ($enumConfigProvider->hasConfig($className, $fieldName)) {
            $enumFieldConfig = $enumConfigProvider->getConfig($className, $fieldName);
            $enumCode        = $enumFieldConfig->get('enum_code');
            if (!empty($enumCode)) {
                $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
                if ($enumConfigProvider->hasConfig($enumValueClassName)) {
                    $enumConfig = $enumConfigProvider->getConfig($enumValueClassName);
                    if ($enumConfig->get('immutable')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
