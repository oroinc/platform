<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Configuration;

use Oro\Bundle\ThemeBundle\Form\Configuration\NumberBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

final class NumberBuilderTest extends TestCase
{
    private NumberBuilder $numberBuilder;
    private Packages|MockObject $packages;
    private FormBuilderInterface|MockObject $formBuilder;

    #[\Override]
    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->packages = $this->createMock(Packages::class);

        $this->numberBuilder = new NumberBuilder($this->packages);
    }

    /**
     * @dataProvider getSupportsDataProvider
     */
    public function testSupports(string $type, bool $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            $this->numberBuilder->supports(['type' => $type])
        );
    }

    public function getSupportsDataProvider(): array
    {
        return [
            ['unknown_type', false],
            [NumberBuilder::getType(), true],
        ];
    }

    /**
     * @dataProvider optionDataProvider
     */
    public function testThatOptionBuiltCorrectly(array $option, array $expected): void
    {
        $this->formBuilder
            ->expects(self::once())
            ->method('add')
            ->with(
                $expected['name'],
                $expected['form_type'],
                $expected['options']
            );

        $this->numberBuilder->buildOption($this->formBuilder, $option);
    }

    private function optionDataProvider(): array
    {
        return [
            'with previews' => [
                [
                    'name' => 'general-number',
                    'label' => 'Number',
                    'type' => NumberBuilder::getType(),
                    'default' => 2.22,
                    'previews' => [
                        2.22 => 'path/to/previews/number.png',
                    ],
                ],
                [
                    'name' => 'general-number',
                    'form_type' => NumberType::class,
                    'options' => [
                        'required' => false,
                        'label' => 'Number',
                        'attr' => [
                            'data-role' => 'change-preview',
                            'data-preview-key' => 'general-number'
                        ],
                        'constraints' => [
                            new PositiveOrZero()
                        ]
                    ],
                ],
            ],
            'no previews' => [
                [
                    'name' => 'general-number',
                    'label' => 'Number',
                    'type' => NumberBuilder::getType(),
                ],
                [
                    'name' => 'general-number',
                    'form_type' => NumberType::class,
                    'options' => [
                        'required' => false,
                        'label' => 'Number',
                        'attr' => [],
                        'constraints' => [
                            new PositiveOrZero()
                        ]
                    ],
                ],
            ],
        ];
    }
}
