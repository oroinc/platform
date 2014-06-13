<?php

namespace Oro\Bundle\AttachmentBundle\Form\Extension;

use Oro\Bundle\EntityExtendBundle\Form\Extension\ExtendEntityExtension;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityBundle\Form\Type\CustomEntityType;

class AttachmentEntityExtension extends ExtendEntityExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['dynamic_fields_disabled']) {
            return;
        }

        $name = $builder instanceof FormConfigBuilder ? $builder->getName() : $builder->getForm()->getName();
        if ($name == CustomEntityType::NAME || empty($options['data_class'])) {
            return;
        }

        $className = $options['data_class'];
        if (!$this->configManager->getProvider('extend')->hasConfig($className)) {
            return;
        }

        if (!$this->hasActiveFields($className)) {
            return;
        }

        $builder->add(
            'additional',
            CustomEntityType::NAME,
            array(
                'inherit_data' => true,
                'class_name' => $className
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }
}
