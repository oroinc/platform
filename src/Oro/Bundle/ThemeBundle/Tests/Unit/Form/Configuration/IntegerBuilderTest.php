<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Configuration;

use Oro\Bundle\ThemeBundle\Form\Configuration\IntegerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

final class IntegerBuilderTest extends TestCase
{
    private IntegerBuilder $integerBuilder;
    private Packages|MockObject $packages;
    private FormBuilderInterface|MockObject $formBuilder;

    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->packages = $this->createMock(Packages::class);
        $this->integerBuilder = new IntegerBuilder($this->packages);
    }

    /**
     * @dataProvider getSupportsDataProvider
     */
    public function testSupports(string $type, bool $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            $this->integerBuilder->supports(['type' => $type])
        );
    }

    public function getSupportsDataProvider(): array
    {
        return [
            ['unknown_type', false],
            [IntegerBuilder::getType(), true],
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

        $this->integerBuilder->buildOption($this->formBuilder, $option);
    }

    private function optionDataProvider(): array
    {
        return [
            'with previews' => [
                [
                    'name' => 'general-integer',
                    'label' => 'Integer',
                    'type' => IntegerBuilder::getType(),
                    'default' => 2,
                    'previews' => [
                        2 => 'path/to/previews/integer.png',
                    ],
                ],
                [
                    'name' => 'general-integer',
                    'form_type' => IntegerType::class,
                    'options' => [
                        'required' => false,
                        'label' => 'Integer',
                        'attr' => [
                            'data-role' => 'change-preview',
                            'data-preview-key' => 'general-integer'
                        ],
                        'constraints' => [
                            new PositiveOrZero()
                        ]
                    ],
                ],
            ],
            'no previews' => [
                [
                    'name' => 'general-integer',
                    'label' => 'Integer',
                    'type' => IntegerBuilder::getType(),
                ],
                [
                    'name' => 'general-integer',
                    'form_type' => IntegerType::class,
                    'options' => [
                        'required' => false,
                        'label' => 'Integer',
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
