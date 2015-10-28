<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;

class OroAutocompleteType extends AbstractType
{
    const NAME = 'oro_autocomplete';

    /**
     * @var SearchRegistry
     */
    protected $searchRegistry;

    /**
     * @param SearchRegistry $registry
     */
    public function __construct(SearchRegistry $registry)
    {
        $this->searchRegistry = $registry;
    }

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
            'selection_template_twig' => '',
            'properties' => [],
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
            'properties' => $autocompleteOptions['properties'],
        ];

        $routeParameters = [
            'per_page' => $autocompleteOptions['per_page'],
        ];

        if (empty($componentOptions['route_name']) && !empty($autocompleteOptions['alias'])) {
            $componentOptions['route_name'] = 'oro_form_autocomplete_search';
            $routeParameters['name'] = $autocompleteOptions['alias'];
        }

        if (empty($componentOptions['properties']) && !empty($autocompleteOptions['alias'])) {
            $searchHandler = $this->searchRegistry->getSearchHandler($autocompleteOptions['alias']);
            $componentOptions['properties'] = $searchHandler->getProperties();
        }

        $componentOptions['route_parameters'] = array_replace($componentOptions['route_parameters'], $routeParameters);

        $view->vars['autocomplete'] = $autocompleteOptions;
        $view->vars['componentModule'] = $autocompleteOptions['componentModule'];
        $view->vars['componentOptions'] = $componentOptions;
    }
}
