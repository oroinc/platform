<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OroAutocompleteType extends AbstractType
{
    const NAME = 'oro_autocomplete';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaultConfigs = [
            'route_name' => '',
            'route_parameters' => [],
            'alias' => '',
            'per_page' => 10,
            'result_template_twig' => '',
            'componentModule' => 'oro/autocomplete-component',
        ];
        $resolver->setDefaults(
            [
                'autocomplete' => $defaultConfigs,
            ]
        );
        $this->setConfigsNormalizer($resolver, $defaultConfigs);
    }

    /**
     * @param OptionsResolver $resolver
     * @param array $defaultConfigs
     */
    protected function setConfigsNormalizer(OptionsResolver $resolver, array $defaultConfigs)
    {
        $resolver->setNormalizer(
            'autocomplete',
            function (Options $options, $configs) use ($defaultConfigs) {
                return array_replace_recursive($defaultConfigs, $configs);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $autocompleteOptions = $options['autocomplete'];

        $componentOptions = [
            'route_name' => $autocompleteOptions['route_name'],
            'route_parameters' => $autocompleteOptions['route_parameters'],
        ];

        $routeParameters = [
            'per_page' => $autocompleteOptions['per_page'],
        ];

        if (empty($componentOptions['route_name']) && !empty($autocompleteOptions['alias'])) {
            $componentOptions['route_name'] = 'oro_form_autocomplete_search';
            $routeParameters['name'] = $autocompleteOptions['alias'];
        }

        $componentOptions['route_parameters'] = array_replace($componentOptions['route_parameters'], $routeParameters);

        $view->vars['autocomplete'] = $autocompleteOptions;
        $view->vars['componentModule'] = $autocompleteOptions['componentModule'];
        $view->vars['componentOptions'] = $componentOptions;
    }
}
