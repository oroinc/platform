<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetFilterType;
use Oro\Bundle\QueryDesignerBundle\Form\Type\FilterType;

class WidgetFilterTypeTest extends TypeTestCase
{
    /** @var WidgetFilterType */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->formType = new WidgetFilterType();
        parent::setUp();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_dashboard_query_filter', $this->formType->getName());
    }

    public function testSubmitValidData()
    {
        $formData = [
            'entity'     => 'TestClass',
            'definition' => '{"filters":[]}'
        ];
        $form     = $this->factory->create(new WidgetFilterType(), null, ['entity' => 'TestClass']);
        $form->submit($formData);

        $this->assertTrue($form->isValid());
        $this->assertEquals(
            ['entity' => 'TestClass', 'definition' => ['filters' => []]],
            $form->getData()
        );
    }

    /**
     * @dataProvider testViewDataProvider
     *
     * @param array $options
     * @param array $expectedData
     * @param array $value
     */
    public function testView(array $options, array $expectedData, $value = [])
    {
        $form    = $this->factory->create(
            new WidgetFilterType(),
            $value,
            array_merge(['entity' => 'TestClass', 'widgetType' => 'test_widget'], $options)
        );
        $view    = $form->createView();
        $this->assertEquals('test_widget', $view->vars['widgetType']);
        $this->assertEquals($expectedData['collapsible'], $view->vars['collapsible']);
        $this->assertEquals($expectedData['collapsed'], $view->vars['collapsed']);
    }

    public function testViewDataProvider()
    {
        return [
            'default options' => [
                [],
                ['collapsible' => false, 'collapsed' => false],
            ],
            'enable expand_filled' => [
                ['expand_filled' => true],
                ['collapsible' => false, 'collapsed' => true],
            ],
            'enable expand_filled with value' => [
                ['expand_filled' => true],
                ['collapsible' => false, 'collapsed' => false],
                ['definition' => ['filters' => [1]]]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $preLoadedExtension = new PreloadedExtension(
            ['oro_query_designer_filter' => new FilterType()],
            []
        );

        return array_merge(parent::getExtensions(), [$preLoadedExtension]);
    }
}
