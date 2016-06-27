<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\ChoiceTreeFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class ChoiceTreeFilterTest extends OrmTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface */
    protected $formFactory;

    /** @var ChoiceTreeFilter */
    protected $filter;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RouterInterface */
    protected $router;

    protected function setUp()
    {
        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')->disableOriginalConstructor()
            ->getMock();
        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $this->filter = new ChoiceTreeFilter(
            $this->formFactory,
            new FilterUtility(),
            $registry,
            $this->router
        );
    }

    public function testGetMetadata()
    {
        $this->initMockFormFactory();
        $expectedMetadata = $this->getDefaultExpectedData();
        $params = [];
        $this->filter->init('filter', $params);
        $metadata = $this->filter->getMetadata();

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testMetadataParameterLazyTrue()
    {
        $this->initMockFormFactory();

        $params = [
            'options' =>[
                'lazy' => true
            ]
        ];

        $expectedMetadata = $this->getDefaultExpectedData();
        $expectedMetadata['lazy'] = true;

        $this->filter->init('filter', $params);
        $metadata = $this->filter->getMetadata();

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testMetadataParameterAutocompleteAlias()
    {
        $this->initMockFormFactory();

        $params = [
            'autocomplete_alias' => 'test_alias'
        ];

        $expectedMetadata = $this->getDefaultExpectedData();
        $expectedMetadata['autocomplete_alias'] = 'test_alias';

        $this->filter->init('filter', $params);
        $metadata = $this->filter->getMetadata();

        self::assertEquals($expectedMetadata, $metadata);
    }

    public function testMetadataParameterAutocompleteUrl()
    {
        $this->initMockFormFactory();
        $this->router->expects(self::once())->method('generate')->with('test_route_name')->willReturn('test_url');

        $params = [
            'autocomplete_url' => 'test_route_name'
        ];

        $expectedMetadata = $this->getDefaultExpectedData();
        $expectedMetadata['autocomplete_url'] = 'test_url';


        $this->filter->init('filter', $params);
        $metadata = $this->filter->getMetadata();

        self::assertEquals($expectedMetadata, $metadata);
    }


    public function testMetadataParameterRenderedPropertyName()
    {
        $this->initMockFormFactory();
        $params = [
            'renderedPropertyName' => 'test_field_name'
        ];

        $expectedMetadata = $this->getDefaultExpectedData();
        $expectedMetadata['renderedPropertyName'] = 'test_field_name';


        $this->filter->init('filter', $params);
        $metadata = $this->filter->getMetadata();

        self::assertEquals($expectedMetadata, $metadata);
    }

    protected function getDefaultExpectedData()
    {
        return [
            'name' => 'filter',
            'label' => 'Filter',
            'choices' => [],
            'lazy' => false,
            'type' => 'choice-tree',
            'data' => false,
            'autocomplete_alias' => false,
            'autocomplete_url' => null,
            'renderedPropertyName' => false,
        ];
    }

    protected function initMockFormFactory()
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $formViewType = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()->getMock();
        $formViewType->vars['choices'] = [];
        $formView = $this->getMockBuilder('Symfony\Component\Form\FormView')->disableOriginalConstructor()->getMock();
        $formView->children['type'] = $formViewType;
        $form->expects(self::once())->method('createView')->willReturn($formView);
        $this->formFactory->expects(self::once())->method('create')->willReturn($form);
    }
}
