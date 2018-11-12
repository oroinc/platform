<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetSortByType;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class WidgetSortByTypeTest extends TypeTestCase
{
    /**
     * @var EntityFieldProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fieldProvider;

    protected function setUp()
    {
        $this->fieldProvider = $this->createMock(EntityFieldProvider::class);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([new WidgetSortByType($this->fieldProvider)], [])
        ];
    }

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

        $this->fieldProvider->expects($this->any())
            ->method('getFields')
            ->with('TestClass')
            ->will($this->returnValue($fields));

        $form = $this->factory->create(WidgetSortByType::class, null, ['class_name' => 'TestClass']);
        $form->submit($formData);

        $this->assertTrue($form->isValid());
        $this->assertEquals(
            $formData,
            $form->getData()
        );
    }
}
