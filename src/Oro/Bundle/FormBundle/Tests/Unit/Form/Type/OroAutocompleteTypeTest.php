<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\OroAutocompleteType;

class OroAutocompleteTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OroAutocompleteType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new OroAutocompleteType();
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->formType);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_autocomplete', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('text', $this->formType->getParent());
    }

    /**
     * @param array $options
     * @param array $expectedFormOptions
     * @param array $expectedComponentOptions
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm(array $options, array $expectedFormOptions, array $expectedComponentOptions)
    {
        $form = $this->factory->create($this->formType, null, ['autocomplete' => $options]);
        $view = $form->createView();

        $formOptions = $form->getConfig()->getOption('autocomplete');
        $this->assertEquals($formOptions, $expectedFormOptions);

        $this->assertArrayHasKey('autocomplete', $view->vars);
        $this->assertArrayHasKey('componentModule', $view->vars);
        $this->assertArrayHasKey('componentOptions', $view->vars);

        $this->assertEquals($view->vars['autocomplete'], $formOptions);
        $this->assertEquals($view->vars['componentModule'], $formOptions['componentModule']);
        $this->assertEquals($view->vars['componentOptions'], $expectedComponentOptions);
    }

    /**
     * @return array
     */
    public function buildFormDataProvider()
    {
        $defaultConfigs = [
            'route_name' => '',
            'route_parameters' => [],
            'alias' => '',
            'per_page' => 10,
            'result_template_twig' => '',
            'componentModule' => 'oro/autocomplete-component',
        ];

        $routeOptions = [
            'route_name' => 'autocomplete_route',
            'route_parameters' => ['param' => 'value'],
        ];

        $aliasOptions = [
            'alias' => 'autocomplete_alias',
        ];

        return array(
            'without options' => array(
                'options' => [],
                'expectedFormOptions' => $defaultConfigs,
                'expectedComponentOptions' => [
                    'route_name' => '',
                    'route_parameters' => [
                        'per_page' => $defaultConfigs['per_page']
                    ],
                ],
            ),
            'with route' => array(
                'options' => $routeOptions,
                'expectedFormOptions' => array_merge($defaultConfigs, $routeOptions),
                'expectedComponentOptions' => [
                    'route_name' => $routeOptions['route_name'],
                    'route_parameters' => array_merge(
                        ['per_page' => $defaultConfigs['per_page']],
                        $routeOptions['route_parameters']
                    ),
                ],
            ),
            'with alias' => array(
                'options' => $aliasOptions,
                'expectedFormOptions' => array_merge($defaultConfigs, $aliasOptions),
                'expectedComponentOptions' => [
                    'route_name' => 'oro_form_autocomplete_search',
                    'route_parameters' => [
                        'per_page' => $defaultConfigs['per_page'],
                        'name' => $aliasOptions['alias'],
                    ],
                ],
            ),
        );
    }
}
