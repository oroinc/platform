<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetSortByType;

class WidgetSortByTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $fields = [
            [
                'name' => 'first',
                'label' => 'firstLabel',
            ],
            [
                'name' => 'second',
                'label' => 'secondLabel',
            ]
        ];

        $formData = [
            'property' => 'first',
            'order' => 'ASC',
            'className' => 'TestClass',
        ];

        $fieldProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldProvider->expects($this->any())
            ->method('getFields')
            ->with('TestClass')
            ->will($this->returnValue($fields));

        $form = $this->factory->create(new WidgetSortByType($fieldProvider), null, ['class_name' => 'TestClass']);
        $form->submit($formData);

        $this->assertTrue($form->isValid());
        $this->assertEquals(
            $formData,
            $form->getData()
        );
    }
}
