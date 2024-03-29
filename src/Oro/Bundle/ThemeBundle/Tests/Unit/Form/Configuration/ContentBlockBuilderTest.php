<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Configuration;

use Oro\Bundle\CMSBundle\Form\Type\ContentBlockSelectType;
use Oro\Bundle\ThemeBundle\Form\Configuration\ContentBlockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilder;

final class ContentBlockBuilderTest extends TestCase
{
    private ContentBlockBuilder $contentBlockBuilder;

    private DataTransformerInterface|MockObject $transformer;

    private FormBuilder|MockObject $formBuilder;

    protected function setUp(): void
    {
        $this->contentBlockBuilder = new ContentBlockBuilder(
            $this->transformer = $this->createMock(DataTransformerInterface::class)
        );

        $this->formBuilder = $this->createMock(FormBuilder::class);
    }

    /**
     * @dataProvider getSupportsDataProvider
     */
    public function testSupports(string $type, bool $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            $this->contentBlockBuilder->supports(['type' => $type])
        );
    }

    public function getSupportsDataProvider(): array
    {
        return [
            ['unknown_type', false],
            [ContentBlockBuilder::getType(), true],
        ];
    }

    public function testThatBuilderDataReverseTransformConfigured(): void
    {
        $this->transformer
            ->expects(self::any())
            ->method('transform')
            ->willReturn('identifier');

        $this->formBuilder
            ->expects(self::once())
            ->method('addModelTransformer')
            ->with(self::callback(function (CallbackTransformer $callbackTransformer) {
                self::assertEquals([], $callbackTransformer->reverseTransform([]));
                self::assertEquals(
                    ['blockName'=> 'identifier'],
                    $callbackTransformer->reverseTransform(['blockName'=> 'identifier'])
                );
                self::assertEquals(
                    ['blockName'=> 'identifier'],
                    $callbackTransformer->reverseTransform(['blockName'=> new \stdClass()])
                );

                return true;
            }));
        $this->formBuilder->expects(self::once())
            ->method('add')
            ->with(
                'blockName',
                ContentBlockSelectType::class,
                [
                    'required' => false,
                    'label' => 'label',
                    'empty_data' => 'default',
                    'attr' => [],
                ]
            );

        $this->contentBlockBuilder->buildOption(
            $this->formBuilder,
            [
                'name' => 'blockName',
                'label' => 'label',
                'default' => 'default',
                'options' => [
                    'required' => false,
                ],
            ]
        );
    }

    public function testThatBuilderDataTransformConfigured(): void
    {
        $this->transformer
            ->expects(self::any())
            ->method('reverseTransform')
            ->with('identifier')
            ->willReturn(new \stdClass());

        $this->formBuilder
            ->expects(self::once())
            ->method('addModelTransformer')
            ->with(self::callback(function (CallbackTransformer $callbackTransformer) {
                self::assertEquals([], $callbackTransformer->transform([]));
                self::assertEquals(
                    ['blockName'=> new \stdClass()],
                    $callbackTransformer->transform(['blockName'=> new \stdClass()])
                );
                self::assertEquals(
                    ['blockName'=> new \stdClass()],
                    $callbackTransformer->transform(['blockName'=> 'identifier'])
                );

                return true;
            }));
        $this->formBuilder->expects(self::once())
            ->method('add')
            ->with(
                'blockName',
                ContentBlockSelectType::class,
                [
                    'required' => false,
                    'label' => 'label',
                    'empty_data' => 'default',
                    'attr' => [],
                ]
            );

        $this->contentBlockBuilder->buildOption(
            $this->formBuilder,
            [
                'name' => 'blockName',
                'label' => 'label',
                'default' => 'default',
                'options' => [
                    'required' => false,
                ],
            ]
        );
    }
}
