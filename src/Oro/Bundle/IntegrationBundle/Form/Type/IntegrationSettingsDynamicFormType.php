<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationSettingsDynamicFormType extends AbstractType
{
    const NAME = 'oro_integration_integration_settings_type';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['fields'] as $fieldName => $field) {
            $builder->add($fieldName, $field['type'], $field['options']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        /**
         * @var array $fields - fields to create
         * print_r($fields):
         *      Array(
         *          [FIELD_NAME] => Array(
         *              [type] => text
         *              [options] => Array(
         *                  [label] => 'Some Label'
         *                  [required] => true
         *              )
         *      )
         */
        $resolver->setRequired('fields');
        $resolver->setAllowedTypes('fields', 'array');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
