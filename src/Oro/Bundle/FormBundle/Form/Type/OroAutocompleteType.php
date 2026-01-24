<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for autocomplete text input with dynamic search results.
 *
 * This type provides an autocomplete field that queries a search handler to retrieve
 * matching results as the user types. It supports configurable search routes, aliases,
 * pagination, and custom result formatting through templates and properties.
 */
class OroAutocompleteType extends AbstractType
{
    const NAME = 'oro_autocomplete';

    /**
     * @var SearchRegistry
     */
    protected $searchRegistry;

    public function __construct(SearchRegistry $registry)
    {
        $this->searchRegistry = $registry;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return TextType::class;
    }

    #[\Override]
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

    #[\Override]
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
