<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailTemplateSelectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = function (Options $options) {
            if (empty($options['selectedEntity'])) {
                return [];
            }

            return null;
        };

        $defaultConfigs = array(
            'placeholder' => 'oro.email.form.choose_template',
        );

        // this normalizer allows to add/override config options outside.
        $that              = $this;
        $configsNormalizer = function (Options $options, $configs) use (&$defaultConfigs, $that) {
            return array_merge($defaultConfigs, $configs);
        };

        $resolver->setRequired(
            array(
                'data_route',
                'depends_on_parent_field'
            )
        );

        $resolver->setDefaults(
            array(
                'label'                   => null,
                'class'                   => 'OroEmailBundle:EmailTemplate',
                'choice_label'            => 'name',
                'query_builder'           => null,
                'depends_on_parent_field' => 'entityName',
                'target_field'            => null,
                'selectedEntity'          => null,
                'choices'                 => $choices,
                'configs'                 => $defaultConfigs,
                'placeholder'             => '',
                'empty_data'              => null,
                'required'                => true,
                'data_route'              => 'oro_api_get_emailtemplates',
                'data_route_parameter'    => 'entityName'
            )
        );
        $resolver->setNormalizer('configs', $configsNormalizer);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $config = $form->getConfig();
        $view->vars['depends_on_parent_field'] = $config->getOption('depends_on_parent_field');
        $view->vars['data_route'] = $config->getOption('data_route');
        $view->vars['data_route_parameter'] = $config->getOption('data_route_parameter');
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
        return 'oro_email_template_list';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return Select2TranslatableEntityType::class;
    }
}
