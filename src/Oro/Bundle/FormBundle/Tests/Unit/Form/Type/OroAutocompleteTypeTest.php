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
            'selection_template_twig' => '',
            'properties' => [],
            'componentModule' => 'oro/autocomplete-component',
        ];

        $routeOptions = [
            'route_name' => 'autocomplete_route',
            'route_parameters' => ['param' => 'value'],
            'properties' => ['property'],
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
                    'properties' => [],
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
                    'properties' => ['property'],
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
                    'properties' => $this->getMockSearchHandler()->getProperties(),
                ],
            ),
        );
    }
}
