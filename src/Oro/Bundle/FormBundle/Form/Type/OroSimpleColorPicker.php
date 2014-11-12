<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OroSimpleColorPicker extends AbstractType
{
    /** @var array */
    protected static $colorSchema = [
        'short' => [
            'translatable' => true,
            'colors'       => [
                '#5484ED' => 'oro.form.color.bold_blue',
                '#A4BDFC' => 'oro.form.color.blue',
                '#46D6DB' => 'oro.form.color.turquoise',
                '#7AE7BF' => 'oro.form.color.green',
                '#51B749' => 'oro.form.color.bold_green',
                '#FBD75B' => 'oro.form.color.yellow',
                '#FFB878' => 'oro.form.color.orange',
                '#FF887C' => 'oro.form.color.red',
                '#DC2127' => 'oro.form.color.bold_red',
                '#DBADFF' => 'oro.form.color.purple',
                '#E1E1E1' => 'oro.form.color.gray'
            ]
        ],
        'long'  => [
            'translatable' => false,
            'colors'       => [
                '#AC725E' => '#AC725E',
                '#D06B64' => '#D06B64',
                '#F83A22' => '#F83A22',
                '#FA573C' => '#FA573C',
                '#FF7537' => '#FF7537',
                '#FFAD46' => '#FFAD46',
                '#42D692' => '#42D692',
                '#16A765' => '#16A765',
                '#7BD148' => '#7BD148',
                '#B3DC6C' => '#B3DC6C',
                '#FBE983' => '#FBE983',
                '#FAD165' => '#FAD165',
                '#92E1C0' => '#92E1C0',
                '#9FE1E7' => '#9FE1E7',
                '#9FC6E7' => '#9FC6E7',
                '#4986E7' => '#4986E7',
                '#9A9CFF' => '#9A9CFF',
                '#B99AFF' => '#B99AFF',
                '#C2C2C2' => '#C2C2C2',
                '#CABDBF' => '#CABDBF',
                '#CCA6AC' => '#CCA6AC',
                '#F691B2' => '#F691B2',
                '#CD74E6' => '#CD74E6',
                '#A47AE2' => '#A47AE2'
            ]
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $defaultConfigs = array(
            'is_translated_option' => false
        );

        $resolver
            ->setDefaults(
                [
                    'choices'      => function (Options $options) {
                        return $this->getChoices($options['color_schema']);
                    },
                    'color_schema' => 'short', // short, long, custom/null
                    'picker'       => false,
                    'picker_delay' => 0,
                    'configs'      => $defaultConfigs
                ]
            )
            ->setNormalizers(
                [
                    'configs' => function (Options $options, $configs) use ($defaultConfigs) {
                        if (isset(static::$colorSchema[$options['color_schema']])
                            && !static::$colorSchema[$options['color_schema']]['translatable']
                        ) {
                            $defaultConfigs['is_translated_option'] = true;
                        }

                        return array_merge($defaultConfigs, $configs);
                    }
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['configs'] = $options['picker']
            ? ['picker' => true, 'pickerDelay' => $options['picker_delay']]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_simple_color_picker';
    }

    /**
     * @param string $colorSchema
     *
     * @return mixed
     */
    protected function getChoices($colorSchema)
    {
        return static::$colorSchema[$colorSchema]['colors'];
    }
}
