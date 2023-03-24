<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form\Type;

use Oro\Bundle\DashboardBundle\Form\Type\DependentDateWidgetDateRangeType;
use Oro\Bundle\DashboardBundle\Form\Type\WidgetDateRangeType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class DependentDateWidgetDateRangeTypeTest extends TypeTestCase
{
    private const CHOICES = [
        'oro.dashboard.widget.filter.dependent_date_range.choices.none' =>
            AbstractDateFilterType::TYPE_NONE,
        'oro.dashboard.widget.filter.dependent_date_range.choices.starting_at' =>
            AbstractDateFilterType::TYPE_MORE_THAN,
    ];

    private DateModifierProvider $dateModifier;

    private TranslatorInterface|MockObject $translator;

    private EventSubscriberInterface|MockObject $subscriber;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->dateModifier = new DateModifierProvider();
        $this->subscriber = $this->createMock(EventSubscriberInterface::class);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $type = new DependentDateWidgetDateRangeType($this->translator);
        $filterType = new FilterType($this->translator);
        $dateRangeFilterType = new DateRangeFilterType($this->translator, $this->dateModifier, $this->subscriber);
        $parentType = new WidgetDateRangeType($this->translator);

        return [
            new PreloadedExtension([$type, $parentType, $dateRangeFilterType, $filterType], []),
        ];
    }

    public function testConfigureOptions(): void
    {
        $form = $this->factory->create(DependentDateWidgetDateRangeType::class);

        self::assertEquals(self::CHOICES, $form->getConfig()->getOption('operator_choices'));
    }

    /**
     * @dataProvider validDataProvider
     */
    public function testSubmitValidData(array $data): void
    {
        $form = $this->factory->create(DependentDateWidgetDateRangeType::class);

        $form->submit($data);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    public function validDataProvider(): array
    {
        return [
            'none' => [
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_NONE,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
            'empty starting at' => [
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    'value' => [
                        'start' => null,
                        'end' => null,
                    ],
                    'part' => 'value',
                ],
            ],
            'starting at' => [
                'data' => [
                    'type' => AbstractDateFilterType::TYPE_MORE_THAN,
                    'value' => [
                        'start' => new \DateTime('today', new \DateTimeZone('UTC')),
                        'end' => null,
                    ],
                    'part' => 'value',
                ],
            ],
        ];
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testSubmitInvalidData(array $data): void
    {
        $form = $this->factory->create(DependentDateWidgetDateRangeType::class);

        $form->submit($data);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
    }

    public function invalidDataProvider(): array
    {
        return [
            'unknown type' => [
                'data' => [
                    'type' => \PHP_INT_MAX,
                    'value' => [],
                    'part' => 'value',
                ],
            ],
        ];
    }
}
