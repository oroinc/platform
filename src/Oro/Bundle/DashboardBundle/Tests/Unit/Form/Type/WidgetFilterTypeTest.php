<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetFilterType;
use Oro\Bundle\QueryDesignerBundle\Form\Type\FilterType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class WidgetFilterTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $formData = [
            'entity'     => 'TestClass',
            'definition' => '{"filters":[]}'
        ];
        $form     = $this->factory->create(WidgetFilterType::class, null, ['entity' => 'TestClass']);
        $form->submit($formData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(
            ['entity' => 'TestClass', 'definition' => ['filters' => []]],
            $form->getData()
        );
    }

    /**
     * @dataProvider testViewDataProvider
     */
    public function testView(array $options, array $expectedData, array $value = [])
    {
        $form = $this->factory->create(
            WidgetFilterType::class,
            $value,
            array_merge(['entity' => 'TestClass', 'widgetType' => 'test_widget'], $options)
        );
        $view = $form->createView();
        $this->assertEquals('test_widget', $view->vars['widgetType']);
        $this->assertEquals($expectedData['collapsible'], $view->vars['collapsible']);
        $this->assertEquals($expectedData['collapsed'], $view->vars['collapsed']);
    }

    public function testViewDataProvider(): array
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
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return array_merge(parent::getExtensions(), [
            new PreloadedExtension([new FilterType()], [])
        ]);
    }
}
