<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type provides functionality to select color
 */
abstract class AbstractSimpleColorPickerType extends AbstractType
{
    /** @var array */
    protected static $colorSchema = [
        'short' => [
            'translatable' => true,
            'colors'       => [
                '#6D8DD4' => 'oro.form.color.bold_blue',
                '#76AAC5' => 'oro.form.color.blue',
                '#5C9496' => 'oro.form.color.turquoise',
                '#99B3AA' => 'oro.form.color.green',
                '#547C51' => 'oro.form.color.bold_green',
                '#C3B172' => 'oro.form.color.yellow',
                '#C98950' => 'oro.form.color.orange',
                '#D28E87' => 'oro.form.color.red',
                '#A24A4D' => 'oro.form.color.bold_red',
                '#A285B8' => 'oro.form.color.purple',
                '#949CA1' => 'oro.form.color.gray'
            ]
        ],
        'long'  => [
            'translatable' => false,
            'colors'       => [
                '#A57261' => '#A57261',
                '#CD7B6C' => '#CD7B6C',
                '#A92F1F' => '#A92F1F',
                '#CD5642' => '#CD5642',
                '#DE703F' => '#DE703F',
                '#E09B45' => '#E09B45',
                '#80C4A6' => '#80C4A6',
                '#368360' => '#368360',
                '#96C27C' => '#96C27C',
                '#CADEAE' => '#CADEAE',
                '#E3D47D' => '#E3D47D',
                '#D1C15C' => '#FAD165',
                '#ACD5C4' => '#ACD5C4',
                '#9EC8CC' => '#9EC8CC',
                '#8EADC7' => '#8EADC7',
                '#5978A9' => '#5978A9',
                '#AA9FC2' => '#AA9FC2',
                '#C2C2C2' => '#C2C2C2',
                '#CABDBF' => '#CABDBF',
                '#CCA6AC' => '#CCA6AC',
                '#AF6C82' => '#AF6C82',
                '#895E95' => '#895E95',
                '#7D6D94' => '#7D6D94'
            ]
        ]
    ];

    /** @var ConfigManager */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(
                [
                    'translatable'      => false,
                    'allow_empty_color' => false,
                    'empty_color'       => null,
                    'color_schema'      => 'short', // short, long, config value name, custom/null
                    'picker'            => false,
                    'picker_delay'      => 0
                ]
            )
            ->setNormalizer(
                'color_schema',
                function (Options $options, $colorSchema) {
                    return $colorSchema ?: 'custom';
                }
            )
            ->setNormalizer(
                'translatable',
                function (Options $options, $translatable) {
                    if (isset(static::$colorSchema[$options['color_schema']])
                        && static::$colorSchema[$options['color_schema']]['translatable']
                    ) {
                        $translatable = true;
                    }

                    return $translatable;
                }
            );
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $config = ['theme' => 'fontawesome'];
        if ($options['allow_empty_color']) {
            $this->appendTheme($config, 'with-empty-color');
            if ($options['empty_color']) {
                $config['emptyColor'] = $options['empty_color'];
            }
        }
        if ($options['picker']) {
            $config = array_merge($config, ['picker' => true, 'pickerDelay' => $options['picker_delay']]);
        }
        $view->vars['configs'] = $config;
    }

    /**
     * @param string $colorSchema
     *
     * @return mixed
     *
     * @throws \RuntimeException if the given color schema is unknown
     */
    protected function getColors($colorSchema)
    {
        if (isset(static::$colorSchema[$colorSchema])) {
            return static::$colorSchema[$colorSchema]['colors'];
        }

        $colors = $this->configManager->get($colorSchema);
        if ($colors) {
            return array_combine($colors, $colors);
        }

        throw new \RuntimeException(sprintf('Unknown color schema: "%s".', $colorSchema));
    }

    /**
     * @param array  $config
     * @param string $cssClass
     */
    protected function appendTheme(array &$config, $cssClass)
    {
        if (!isset($config['theme'])) {
            $config['theme'] = $cssClass;
        } else {
            $config['theme'] .= ' ' . $cssClass;
        }
    }
}
