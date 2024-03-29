<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Configuration;

use Oro\Bundle\CMSBundle\Form\Type\ContentBlockSelectType;
use Oro\Bundle\ThemeBundle\Form\Configuration\ContentBlockBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilder;

final class ContentBlockOptionBuilderTest extends TestCase
{
    private ContentBlockBuilder $contentBlockOptionBuilder;

    private DataTransformerInterface $transformer;

    private FormBuilder $formBuilder;

    protected function setUp(): void
    {
        $this->contentBlockOptionBuilder = new ContentBlockBuilder(
            $this->transformer = $this->createMock(DataTransformerInterface::class)
        );

        $this->formBuilder = $this->createMock(FormBuilder::class);
    }

    public function testThatOptionSelectSupported(): void
    {
        self::assertTrue($this->contentBlockOptionBuilder->supports(['type'=> 'content_block_selector']));
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

        $this->contentBlockOptionBuilder->buildOption($this->formBuilder, $option);
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

        $this->contentBlockOptionBuilder->buildOption(
            $this->formBuilder,
            [
                'name' => 'blockName',
                'label' => 'label',
                'default' => 'default'
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

        $this->contentBlockOptionBuilder->buildOption(
            $this->formBuilder,
            [
                'name' => 'blockName',
                'label' => 'label',
                'default' => 'default'
            ]
        );
    }

    private function optionDataProvider(): array
    {
        return [
            'no previews' => [
                [
                    'name' => 'general-promotional-content-block',
                    'label' => 'Select',
                    'type' => 'content_block_selector',
                    'default' => null,
                    'options' => [
                        'required' => false,
                    ]
                ],
                [
                    'name' => 'general-promotional-content-block',
                    'form_type' => ContentBlockSelectType::class,
                    'options' => [
                        'required' => false,
                        'empty_data' => null,
                        'label' => 'Select',
                        'attr' => []
                    ]
                ]
            ]
        ];
    }
}
