<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

class OroIconType extends AbstractType
{
    const NAME = 'oro_icon_select';

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
            $selectedData = ['id' => $form->getData(), 'text' => $form->getData()];
            if (isset($options['configs']['multiple']) && $options['configs']['multiple']) {
                $selectedData = [$selectedData];
            }
            $vars['attr'] = [
                'data-selected-data' => json_encode($selectedData)
            ];
        }

        $view->vars = array_replace_recursive($view->vars, $vars);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $configFile = $this->kernel->locateResource('@OroFormBundle/Resources/config/config_icon.yml');
        $config = Yaml::parse(file_get_contents($configFile));
        $choices = [];
        foreach ($config['oro_icon_select'] as $label => $value) {
            $choices['oro.form.icon_select.' . $label] = $value;
        }

        $resolver->setDefaults(
            [
                'placeholder' => 'oro.form.choose_value',
                'choices'     => $choices,
                'placeholder' => '',
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
        return Select2ChoiceType::class;
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
