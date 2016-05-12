<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Yaml\Yaml;

class OroIconType extends AbstractType
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $vars = ['configs' => $options['configs']];
        if ($form->getData()) {
            $vars['attr'] = [
                'data-selected-data' => json_encode([['id' => $form->getData(), 'text' => $form->getData()]])
            ];
        }

        $view->vars = array_replace_recursive($view->vars, $vars);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $configFile = $this->kernel->locateResource('@OroFormBundle/Resources/config/config_icon.yml');
        $config      = Yaml::parse(file_get_contents($configFile));
        $choices = array_map(
            function ($value) {
                return 'oro.form.icon_select.' . $value;
            },
            array_flip($config['oro_icon_select'])
        );

        $resolver->setDefaults(
            [
                'placeholder' => 'oro.form.choose_value',
                'choices'     => $choices,
                'empty_value' => '',
                'configs'     => [
                    'placeholder'             => 'oro.form.choose_value',
                    'result_template_twig'    => 'OroFormBundle:Autocomplete:icon/result.html.twig',
                    'selection_template_twig' => 'OroFormBundle:Autocomplete:icon/selection.html.twig',
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_icon_select';
    }
}
