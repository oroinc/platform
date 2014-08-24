<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

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
        /** @var ConfigIdInterface $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();
        $fieldName = $configId instanceof FieldConfigId ? $configId->getFieldName() : null;

        if (!empty($className)) {
            // disable for immutable entities or fields
            $configProvider = $this->configManager->getProvider($configId->getScope());
            if ($configProvider->hasConfig($className, $fieldName)) {
                $immutable = $configProvider->getConfig($className, $fieldName)->get('immutable');
                if (true === $immutable) {
                    return true;
                }
            }
        }

        return false;
    }
}
