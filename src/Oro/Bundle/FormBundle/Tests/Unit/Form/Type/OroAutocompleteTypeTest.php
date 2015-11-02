<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Type\OroAutocompleteType;

class OroAutocompleteTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OroAutocompleteType
     */
    protected $formType;

    /**
     * @var SearchRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchRegistry;

    /**
     * @var SearchHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchHandler;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new OroAutocompleteType($this->getMockSearchRegistry());
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->formType, $this->searchRegistry, $this->searchHandler);
    }

    /**
     * @return SearchRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockSearchRegistry()
    {
        if (!$this->searchRegistry) {
            $this->searchRegistry = $this->getMock('Oro\Bundle\FormBundle\Autocomplete\SearchRegistry');
            $this->searchRegistry->method('getSearchHandler')->willReturn($this->getMockSearchHandler());
        }

        return $this->searchRegistry;
    }

    /**
     * @return SearchHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockSearchHandler()
    {
        if (!$this->searchHandler) {
            $this->searchHandler = $this->getMock('Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface');
            $this->searchHandler->method('getProperties')->willReturn(['code', 'label']);
        }

        return $this->searchHandler;
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
     *
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
        $defaultOptions = [
            'route_name' => '',
            'route_parameters' => [],
            'alias' => '',
            'per_page' => 10,
            'selection_template_twig' => '',
            'properties' => [],
            'componentModule' => 'oro/autocomplete-component',
        ];

        $defaultOptions['route_parameters']['per_page'] = $defaultOptions['per_page'];

        $routeOptions = [
            'route_name' => 'autocomplete_route',
            'route_parameters' => ['param' => 'value'],
            'properties' => ['property'],
        ];
        $routeFormOptions = array_merge($defaultOptions, $routeOptions);
        $routeFormOptions['route_parameters'] = array_merge(
            $routeOptions['route_parameters'],
            $defaultOptions['route_parameters']
        );

        $aliasOptions = [
            'alias' => 'autocomplete_alias',
        ];
        $aliasFormOptions = array_merge(
            $defaultOptions,
            $aliasOptions,
            [
                'route_name' => 'oro_form_autocomplete_search',
                'properties' => $this->getMockSearchHandler()->getProperties(),
            ]
        );
        $aliasFormOptions['route_parameters']['name'] = $aliasOptions['alias'];

        return array(
            'without options' => array(
                'options' => [],
                'expectedFormOptions' => $defaultOptions,
                'expectedComponentOptions' => [
                    'route_name' => $defaultOptions['route_name'],
                    'route_parameters' => $defaultOptions['route_parameters'],
                    'properties' => $defaultOptions['properties'],
                ],
            ),
            'with route' => array(
                'options' => $routeOptions,
                'expectedFormOptions' => $routeFormOptions,
                'expectedComponentOptions' => [
                    'route_name' => $routeFormOptions['route_name'],
                    'route_parameters' => $routeFormOptions['route_parameters'],
                    'properties' => $routeFormOptions['properties'],
                ],
            ),
            'with alias' => array(
                'options' => $aliasOptions,
                'expectedFormOptions' => $aliasFormOptions,
                'expectedComponentOptions' => [
                    'route_name' => $aliasFormOptions['route_name'],
                    'route_parameters' => $aliasFormOptions['route_parameters'],
                    'properties' => $aliasFormOptions['properties'],
                ],
            ),
        );
    }
}
