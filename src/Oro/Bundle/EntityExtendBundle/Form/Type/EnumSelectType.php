<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * An enum value selector based on 'select2' form type
 */
class EnumSelectType extends AbstractEnumType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $defaultConfigs = [
            'allowClear'  => true,
            'placeholder' => 'oro.form.choose_value'
        ];

        $resolver->setDefaults(
            [
                'empty_value' => null,
                'empty_data'  => null,
                'configs'     => $defaultConfigs
            ]
        );

        $resolver->setNormalizers(
            [
                'empty_value' => function (Options $options, $value) {
                    return !$options['expanded'] && !$options['multiple']
                        ? ''
                        : $value;
                },
                // this normalizer allows to add/override config options outside
                'configs' => function (Options $options, $value) use (&$defaultConfigs) {
                    return array_merge($defaultConfigs, $value);
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_translatable_entity';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_enum_select';
    }
}
