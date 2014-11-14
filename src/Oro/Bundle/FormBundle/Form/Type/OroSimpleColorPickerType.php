<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class OroSimpleColorPickerType extends AbstractSimpleColorPickerType
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver
            ->setDefaults(
                [
                    'colors'               => [],
                    'empty_value'          => null,
                    'allow_custom_color'   => false,
                    'custom_color_control' => null // hue, brightness, saturation, or wheel. defaults wheel
                ]
            )
            ->setNormalizers(
                [
                    'colors' => function (Options $options, $colors) {
                        return $options['color_schema'] === 'custom'
                            ? $colors
                            : $this->getColors($options['color_schema']);
                    }
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $data       = $form->getData();
        $pickerData = $this->convertColorsToPickerData($options['colors'], $options['translatable']);

        if ($options['empty_color']) {
            $emptyColorItem = [
                'id'    => null,
                'text'  => $this->translator->trans($options['empty_value']),
                'class' => 'empty-color'
            ];
            if (!$data) {
                $emptyColorItem['selected'] = true;
            }
            array_unshift($pickerData, $emptyColorItem, []);
        }

        $view->vars['allow_custom_color'] = $options['allow_custom_color'];
        if ($options['allow_custom_color']) {
            $this->appendTheme($view->vars['configs'], 'with-custom-color');
            $customColor           = '#FFFFFF';
            $isCustomColorSelected = false;
            if ($data && !isset($options['colors'][$data])) {
                $customColor           = $data;
                $isCustomColorSelected = true;
            }

            $customColorItem = [
                'id'    => $customColor,
                'text'  => $this->translator->trans('oro.form.color.custom'),
                'class' => 'custom-color'
            ];
            if ($isCustomColorSelected) {
                $customColorItem['selected'] = true;
            }
            array_push($pickerData, [], $customColorItem);

            $view->vars['custom_color']          = $customColor;
            $view->vars['custom_color_selected'] = $isCustomColorSelected;

            $view->vars['configs']['custom_color'] = [
                'defaultValue' => $customColor
            ];
            if ($options['custom_color_control']) {
                $view->vars['configs']['custom_color']['control'] = $options['custom_color_control'];
            }
        }

        $view->vars['configs']['data'] = $pickerData;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_simple_color_picker';
    }

    /**
     * @param array $colors
     * @param bool  $translatable
     *
     * @return array
     */
    protected function convertColorsToPickerData($colors, $translatable)
    {
        $data = [];
        foreach ($colors as $clr => $text) {
            $data[] = [
                'id'   => $clr,
                'text' => $translatable ? $this->translator->trans($text) : $text
            ];
        }

        return $data;
    }
}
