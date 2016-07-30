<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class IntegrationSettingsDynamicFormType extends AbstractType
{
    const NAME = 'oro_integration_integration_settings_type';

    /** @var array */
    protected $fields;

    /**
     * @param array $fields - fields to create
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
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->fields as $fieldName => $field) {
            $builder->add($fieldName, $field['type'], $field['options']);
        }
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
