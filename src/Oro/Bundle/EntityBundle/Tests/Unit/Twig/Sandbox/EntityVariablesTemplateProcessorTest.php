<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityDataAccessor;
use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityFormatExtension;
use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariableComputer;
use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesTemplateProcessor;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;

class EntityVariablesTemplateProcessorTest extends TestCase
{
    private TwigEnvironment|MockObject $twigEnvironment;

    private EntityVariableComputer|MockObject $entityVariableComputer;

    private EntityVariablesTemplateProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->twigEnvironment = $this->createMock(TwigEnvironment::class);
        $this->entityVariableComputer = $this->createMock(EntityVariableComputer::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $translator
            ->method('trans')
            ->willReturnCallback(function (string $key) {
                return $key . '.translated';
            });

        $this->processor = new EntityVariablesTemplateProcessor(
            $this->twigEnvironment,
            $this->entityVariableComputer,
            (new InflectorFactory())->build(),
            $translator
        );
    }

    public function testProcessEntityVariablesWhenNoRootEntity(): void
    {
        $templateContent = 'template content';
        $templateData = $this->createMock(TemplateData::class);

        $this->twigEnvironment
            ->expects(self::never())
            ->method(self::anything());

        $this->entityVariableComputer
            ->expects(self::never())
            ->method(self::anything());

        $this->processor->processEntityVariables($templateContent, $templateData);
    }

    /**
     * @dataProvider processEntityVariablesDataProvider
     */
    public function testProcessEntityVariables(
        string $templateContent,
        array $templateParams,
        string $expectedContent
    ): void {
        $templateData = new TemplateData(
            $templateParams,
            $this->createMock(EntityVariableComputer::class),
            $this->createMock(EntityDataAccessor::class),
            'system',
            'entity',
            'computed'
        );

        $this->entityVariableComputer
            ->expects(self::any())
            ->method('computeEntityVariable')
            ->willReturnMap([
                ['entity.computable_variable', $templateData, 'computed.computable_variable'],
                ['entity.filterable_computable_variable', $templateData, 'computed.filterable_computable_variable'],
            ]);

        $entityFormatExtension = $this->createMock(EntityFormatExtension::class);
        $this->twigEnvironment
            ->method('getExtension')
            ->with(EntityFormatExtension::class)
            ->willReturn($entityFormatExtension);

        $entityFormatExtension
            ->method('getSafeFormatExpression')
            ->willReturnMap([
                [
                    'sampleVariable',
                    'entity.sample_variable',
                    'entity',
                    'oro.entity.template_renderer.entity_variable_not_found.translated',
                    '{{ entity.sample_variable }}',
                ],
                [
                    'filterableVariable',
                    'entity.filterable_variable',
                    'entity',
                    'oro.entity.template_renderer.entity_variable_not_found.translated',
                    '{{ entity.filterable_variable|escape }}',
                ],
                [
                    'computableVariable',
                    'computed.computable_variable',
                    'entity',
                    'oro.entity.template_renderer.entity_variable_not_found.translated',
                    '{{ computed.computable_variable }}',
                ],
            ]);

        self::assertEquals($expectedContent, $this->processor->processEntityVariables($templateContent, $templateData));
    }

    public function processEntityVariablesDataProvider(): \Generator
    {
        yield 'empty content' => [
            'templateContent' => '',
            'templateParams' => ['entity' => new User()],
            'expectedContent' => '',
        ];

        yield 'not empty content' => [
            'templateContent' => 'sample content',
            'templateParams' => ['entity' => new User()],
            'expectedContent' => 'sample content',
        ];

        yield 'with entity variable' => [
            'templateContent' => 'sample content with {{ entity.sample_variable }}',
            'templateParams' => ['entity' => new User()],
            'expectedContent' => 'sample content with {{ entity.sample_variable }}',
        ];

        yield 'with filterable entity variable' => [
            'templateContent' => 'sample content with {{ entity.filterable_variable }}',
            'templateParams' => ['entity' => new User()],
            'expectedContent' => 'sample content with {{ entity.filterable_variable|escape }}',
        ];

        yield 'with computable entity variable' => [
            'templateContent' => 'sample content with {{ entity.computable_variable }}',
            'templateParams' => ['entity' => new User()],
            'expectedContent' => 'sample content with {{ computed.computable_variable }}',
        ];

        yield 'with filterable computable variable' => [
            'templateContent' => 'sample content with {{ computed.filterable_computable_variable }}',
            'templateParams' => ['entity' => new User()],
            'expectedContent' => 'sample content with {{ computed.filterable_computable_variable }}',
        ];
    }
}
