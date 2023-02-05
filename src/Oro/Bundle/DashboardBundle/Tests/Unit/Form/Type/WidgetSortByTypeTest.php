<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetSortByType;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class WidgetSortByTypeTest extends TypeTestCase
{
    /** @var EntityFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldProvider;

    protected function setUp(): void
    {
        $this->fieldProvider = $this->createMock(EntityFieldProvider::class);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([new WidgetSortByType($this->fieldProvider)], [])
        ];
    }

    public function testSubmitValidData(): void
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

        $this->fieldProvider->expects(self::any())
            ->method('getEntityFields')
            ->with('TestClass')
            ->willReturn($fields);

        $form = $this->factory->create(WidgetSortByType::class, null, ['class_name' => 'TestClass']);
        $form->submit($formData);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            $formData,
            $form->getData()
        );
    }
}
