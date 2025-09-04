<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form Type for selecting email template
 */
class EmailTemplateSelectType extends AbstractType
{
    #[\Override]
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
                'label' => null,
                'class' => EmailTemplate::class,
                'choice_label' => 'name',
                'query_builder' => null,
                'depends_on_parent_field' => 'entityName',
                'target_field' => null,
                'selectedEntity' => null,
                'choices' => $choices,
                'configs' => $defaultConfigs,
                'placeholder' => '',
                'empty_data' => null,
                'required' => true,
                'data_route' => 'oro_api_get_emailtemplates',
                'data_route_parameter' => 'entityName',
                'includeNonEntity' => false,
                'includeSystemTemplates' => true
            )
        );
        $resolver->setNormalizer('configs', $configsNormalizer);
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $config = $form->getConfig();
        $dependeeFieldName = $config->getOption('depends_on_parent_field');
        $view->vars['depends_on_parent_field'] = $dependeeFieldName;

        // Searches for the dependee field.
        $parentView = $view;
        while ($parentView->parent !== null) {
            $parentView = $parentView->parent;
            if (isset($parentView->children[$dependeeFieldName])) {
                $view->vars['dependee_field_id'] = $parentView->children[$dependeeFieldName]->vars['id'];
            }
        }

        $view->vars['data_route'] = $config->getOption('data_route');
        $view->vars['data_route_parameter'] = $config->getOption('data_route_parameter');
        $view->vars['includeNonEntity'] = (bool)$config->getOption('includeNonEntity');
        $view->vars['includeSystemTemplates'] = (bool)$config->getOption('includeSystemTemplates');
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_email_template_list';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2TranslatableEntityType::class;
    }
}
