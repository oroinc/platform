<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Type\OroAutocompleteType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroAutocompleteTypeTest extends FormIntegrationTestCase
{
    /** @var SearchHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $searchHandler;

    /** @var OroAutocompleteType */
    private $formType;

    protected function setUp(): void
    {
        $searchRegistry = $this->createMock(SearchRegistry::class);
        $searchRegistry->expects(self::any())
            ->method('getSearchHandler')
            ->willReturn($this->getMockSearchHandler());

        $this->formType = new OroAutocompleteType($searchRegistry);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }

    private function getMockSearchHandler(): SearchHandlerInterface
    {
        if (!$this->searchHandler) {
            $this->searchHandler = $this->createMock(SearchHandlerInterface::class);
            $this->searchHandler->expects(self::any())
                ->method('getProperties')
                ->willReturn(['code', 'label']);
        }

        return $this->searchHandler;
    }

    public function testGetParent()
    {
        $this->assertEquals(TextType::class, $this->formType->getParent());
    }

    /**
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm(array $options, array $expectedFormOptions, array $expectedComponentOptions)
    {
        $form = $this->factory->create(OroAutocompleteType::class, null, ['autocomplete' => $options]);
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

    public function buildFormDataProvider(): array
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

        return [
            'without options' => [
                'options' => [],
                'expectedFormOptions' => $defaultOptions,
                'expectedComponentOptions' => [
                    'route_name' => $defaultOptions['route_name'],
                    'route_parameters' => $defaultOptions['route_parameters'],
                    'properties' => $defaultOptions['properties'],
                ],
            ],
            'with route' => [
                'options' => $routeOptions,
                'expectedFormOptions' => $routeFormOptions,
                'expectedComponentOptions' => [
                    'route_name' => $routeFormOptions['route_name'],
                    'route_parameters' => $routeFormOptions['route_parameters'],
                    'properties' => $routeFormOptions['properties'],
                ],
            ],
            'with alias' => [
                'options' => $aliasOptions,
                'expectedFormOptions' => $aliasFormOptions,
                'expectedComponentOptions' => [
                    'route_name' => $aliasFormOptions['route_name'],
                    'route_parameters' => $aliasFormOptions['route_parameters'],
                    'properties' => $aliasFormOptions['properties'],
                ],
            ],
        ];
    }
}
