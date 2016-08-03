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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
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
                $configs = array_replace_recursive($defaultConfigs, $configs);

                $configs['route_parameters']['per_page'] = $configs['per_page'];

                if (empty($configs['route_name']) && !empty($configs['alias'])) {
                    $configs['route_name'] = 'oro_form_autocomplete_search';
                    $configs['route_parameters']['name'] = $configs['alias'];
                }

                if (empty($configs['properties']) && !empty($configs['alias'])) {
                    $searchHandler = $this->searchRegistry->getSearchHandler($configs['alias']);
                    $configs['properties'] = $searchHandler->getProperties();
                }

                return $configs;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $autocompleteOptions = $options['autocomplete'];

        $view->vars['autocomplete'] = $autocompleteOptions;
        $view->vars['componentModule'] = $autocompleteOptions['componentModule'];
        $view->vars['componentOptions'] = [
            'route_name' => $autocompleteOptions['route_name'],
            'route_parameters' => $autocompleteOptions['route_parameters'],
            'properties' => $autocompleteOptions['properties'],
        ];
    }
}
