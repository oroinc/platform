<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Stub\SomeEntity as TestSubEntity2;
use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Stub\TestEntity as TestSubEntity1;
use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Stub\TestEntity1 as TestMainEntity;
use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Stub\TestEntity2 as TestSubEntity3;
use Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox\Stub\TestTemplateStub;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRenderer;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorInterface;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorRegistry;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityPolicy;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\TemplateWrapper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TemplateRendererTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private const ENTITY_VARIABLE_TEMPLATE =
        '{% if %val% is defined %}'
        . '{{ _entity_var("%name%", %val%, %parent%) }}'
        . '{% else %}'
        . '{{ "variable_not_found_message" }}'
        . '{% endif %}';

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $environment;

    /** @var SecurityPolicy */
    private $securityPolicy;

    /** @var TemplateRendererConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var VariableProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $variablesProcessorRegistry;

    /** @var TemplateRenderer */
    private $renderer;

    protected function setUp(): void
    {
        $this->environment = $this->getMockBuilder(Environment::class)
            ->onlyMethods(['createTemplate'])
            ->setConstructorArgs([new ArrayLoader(), ['strict_variables' => true]])
            ->getMock();
        $this->securityPolicy = $this->getMockBuilder(SecurityPolicyInterface::class)
            ->onlyMethods(['checkMethodAllowed', 'checkPropertyAllowed', 'checkSecurity'])
            ->addMethods(['setAllowedProperties', 'setAllowedMethods'])
            ->getMock();
        $this->configProvider = $this->createMock(TemplateRendererConfigProviderInterface::class);
        $this->variablesProcessorRegistry = $this->createMock(VariableProcessorRegistry::class);

        $this->environment->addExtension(new SandboxExtension($this->securityPolicy));

        $this->renderer = new TemplateRendererStub(
            $this->environment,
            $this->configProvider,
            $this->variablesProcessorRegistry,
            (new InflectorFactory())->build()
        );
    }

    private function getEntityVariableTemplate(string $propertyName, string $path, string $parentPath): string
    {
        return strtr(
            self::ENTITY_VARIABLE_TEMPLATE,
            [
                '%name%'   => $propertyName,
                '%val%'    => $path,
                '%parent%' => $parentPath
            ]
        );
    }

    private function expectVariables(array $entityVariableProcessors = [], array $systemVariableValues = []): void
    {
        $entityVariableProcessorsMap = [];
        foreach ($entityVariableProcessors as $entityClass => $processors) {
            $entityVariableProcessorsMap[] = [$entityClass, $processors];
        }
        $this->configProvider->expects(self::any())
            ->method('getEntityVariableProcessors')
            ->willReturnMap($entityVariableProcessorsMap);
        $this->configProvider->expects(self::any())
            ->method('getSystemVariableValues')
            ->willReturn($systemVariableValues);
    }

    public function testConfigureSandbox()
    {
        $entityClass = TestSubEntity1::class;
        $properties = [$entityClass => ['field2']];
        $methods = [$entityClass => ['getField1']];

        $this->configProvider->expects(self::once())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => $properties,
                'methods'            => $methods,
                'accessors'          => [$entityClass => ['field1' => 'getField1', 'field2' => null]],
                'default_formatters' => []
            ]);

        $this->configProvider->expects(self::once())
            ->method('getSystemVariableValues')
            ->willReturn([]);

        $templateStub = new TestTemplateStub($this->environment, '', '');
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->willReturn($templateWrapper);

        $this->renderer->renderTemplate('');

        $entity = new $entityClass;
        $this->securityPolicy->checkPropertyAllowed($entity, 'field2');
        $this->securityPolicy->checkMethodAllowed($entity, 'getField1');
        $this->securityPolicy->checkMethodAllowed($entity, '__toString');
    }

    public function testRenderTemplateForVariablesWithFormatters()
    {
        $entity = new TestMainEntity();
        $entity->setField1('Test');
        $entityClass = get_class($entity);
        $systemVars = ['testVar' => 'test_system'];
        $entityVariableProcessors = [$entityClass => []];
        $defaultFormatters = [$entityClass => ['field1' => 'formatter1']];

        $template = 'test '
            . '<a href="http://example.com">test</a>'
            . ' {{ entity.field1|oro_html_sanitize }}'
            . ' {{ entity.field2|trim|raw }}'
            . ' {{ func(entity.field3) }}'
            . ' {{ system.testVar }}'
            . ' N/A';

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [$entityClass => ['getField1']],
                'accessors'          => [$entityClass => ['field1' => 'getField1']],
                'default_formatters' => $defaultFormatters
            ]);
        $this->expectVariables($entityVariableProcessors, $systemVars);

        $templateParams = [
            'entity' => $entity,
            'system' => $systemVars
        ];

        $templateStub = new TestTemplateStub($this->environment, '', $template);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($template)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, $templateParams);
        self::assertSame($template, $result);
    }

    public function testRenderTemplateForVariablesWithoutFormatters()
    {
        $template = 'test '
            . "\n"
            . '{{ entity.field1 }}'
            . "\n"
            . '{{ entity.field2.field21 }}'
            . "\n"
            . '{{ entity.field2.field22 }}'
            . "\n"
            . '{{ system.currentDate }}';
        $expectedRenderedResult =
            'test '
            . "\n"
            . $this->getEntityVariableTemplate('field1', 'entity.field1', 'entity')
            . "\n"
            . $this->getEntityVariableTemplate('field21', 'entity.field2.field21', 'entity.field2')
            . "\n"
            . $this->getEntityVariableTemplate('field22', 'entity.field2.field22', 'entity.field2')
            . "\n"
            . '{{ system.currentDate }}';

        $entity = new TestMainEntity();

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity) => []
        ]);

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    public function testRenderTemplateForComputedFieldWithUndefinedProcessor()
    {
        $template = '{{ entity.computedField }}';
        $expectedRenderedResult = $this->getEntityVariableTemplate(
            'computedField',
            'entity.computedField',
            'entity'
        );

        $entity = new TestMainEntity();

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity) => [
                'computedField' => [
                    'processor' => 'computedField_processor'
                ]
            ]
        ]);

        $this->variablesProcessorRegistry->expects(self::once())
            ->method('has')
            ->with('computedField_processor')
            ->willReturn(false);
        $this->variablesProcessorRegistry->expects(self::never())
            ->method('get');

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    public function testRenderTemplateForFirstLevelComputedField()
    {
        $template = '{{ entity.computedField }}';
        $expectedRenderedResult = $this->getEntityVariableTemplate(
            'computedField',
            'computed.entity__computedField',
            'entity'
        );

        $entity = new TestMainEntity();
        $computedValue = 'testVal';

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity) => [
                'computedField' => [
                    'processor' => 'computedField_processor'
                ]
            ]
        ]);

        $this->variablesProcessorRegistry->expects(self::once())
            ->method('has')
            ->with('computedField_processor')
            ->willReturn(true);
        $computedFieldProcessor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('computedField_processor')
            ->willReturn($computedFieldProcessor);
        $computedFieldProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function ($variable, $definition, TemplateData $data) use ($computedValue) {
                $data->setComputedVariable($variable, $computedValue);
            });

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    public function testRenderTemplateForFirstLevelComputedFieldButWhenProcessorDoesNotSetComputedValue()
    {
        $template = '{{ entity.computedField }}';
        $expectedRenderedResult = $this->getEntityVariableTemplate(
            'computedField',
            'entity.computedField',
            'entity'
        );

        $entity = new TestMainEntity();

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity) => [
                'computedField' => [
                    'processor' => 'computedField_processor'
                ]
            ]
        ]);

        $this->variablesProcessorRegistry->expects(self::once())
            ->method('has')
            ->with('computedField_processor')
            ->willReturn(true);
        $computedFieldProcessor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('computedField_processor')
            ->willReturn($computedFieldProcessor);
        $computedFieldProcessor->expects(self::once())
            ->method('process');

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    public function testRenderTemplateForFirstLevelComputedAssociation()
    {
        $template = '{{ entity.computedField.field1 }}';
        $expectedRenderedResult = $this->getEntityVariableTemplate(
            'field1',
            'computed.entity__computedField.field1',
            'computed.entity__computedField'
        );

        $entity = new TestMainEntity();
        $entity1 = new TestSubEntity1();

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity)  => [
                'computedField' => [
                    'processor' => 'computedField_processor'
                ]
            ],
            get_class($entity1) => []
        ]);

        $this->variablesProcessorRegistry->expects(self::once())
            ->method('has')
            ->with('computedField_processor')
            ->willReturn(true);
        $computedFieldProcessor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('computedField_processor')
            ->willReturn($computedFieldProcessor);
        $computedFieldProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function ($variable, $definition, TemplateData $data) use ($entity1) {
                $data->setComputedVariable($variable, $entity1);
            });

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    public function testRenderTemplateForFirstLevelComputedAssociationButWhenProcessorDoesNotSetComputedValue()
    {
        $template = '{{ entity.computedField.field1 }}';
        $expectedRenderedResult = $this->getEntityVariableTemplate(
            'field1',
            'entity.computedField.field1',
            'entity.computedField'
        );

        $entity = new TestMainEntity();

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity) => [
                'computedField' => [
                    'processor' => 'computedField_processor'
                ]
            ]
        ]);

        $this->variablesProcessorRegistry->expects(self::once())
            ->method('has')
            ->with('computedField_processor')
            ->willReturn(true);
        $computedFieldProcessor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('computedField_processor')
            ->willReturn($computedFieldProcessor);
        $computedFieldProcessor->expects(self::once())
            ->method('process');

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    public function testRenderTemplateForSecondLevelComputedField()
    {
        $template = '{{ entity.field1.computedField }}';
        $expectedRenderedResult = $this->getEntityVariableTemplate(
            'computedField',
            'computed.entity__field1__computedField',
            'entity.field1'
        );

        $entity1 = new TestSubEntity1();
        $entity = new TestMainEntity();
        $entity->setField1($entity1);
        $computedValue = 'testVal';

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity)  => [],
            get_class($entity1) => [
                'computedField' => [
                    'processor' => 'computedField_processor'
                ]
            ]
        ]);

        $this->variablesProcessorRegistry->expects(self::once())
            ->method('has')
            ->with('computedField_processor')
            ->willReturn(true);
        $computedFieldProcessor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('computedField_processor')
            ->willReturn($computedFieldProcessor);
        $computedFieldProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function ($variable, $definition, TemplateData $data) use ($computedValue) {
                $data->setComputedVariable($variable, $computedValue);
            });

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    public function testRenderTemplateForSecondLevelComputedFieldButWhenProcessorDoesNotSetComputedValue()
    {
        $template = '{{ entity.field1.computedField }}';
        $expectedRenderedResult = $this->getEntityVariableTemplate(
            'computedField',
            'entity.field1.computedField',
            'entity.field1'
        );

        $entity1 = new TestSubEntity1();
        $entity = new TestMainEntity();
        $entity->setField1($entity1);

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity)  => [],
            get_class($entity1) => [
                'computedField' => [
                    'processor' => 'computedField_processor'
                ]
            ]
        ]);

        $this->variablesProcessorRegistry->expects(self::once())
            ->method('has')
            ->with('computedField_processor')
            ->willReturn(true);
        $computedFieldProcessor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('computedField_processor')
            ->willReturn($computedFieldProcessor);
        $computedFieldProcessor->expects(self::once())
            ->method('process');

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    public function testRenderTemplateForSecondLevelComputedAssociation()
    {
        $template = '{{ entity.field1.computedField.field11 }}';
        $expectedRenderedResult = $this->getEntityVariableTemplate(
            'field11',
            'computed.entity__field1__computedField.field11',
            'computed.entity__field1__computedField'
        );

        $entity1 = new TestSubEntity1();
        $entity = new TestMainEntity();
        $entity->setField1($entity1);
        $entity2 = new TestSubEntity2();

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity)  => [],
            get_class($entity1) => [
                'computedField' => [
                    'processor' => 'computedField_processor'
                ]
            ],
            get_class($entity2) => []
        ]);

        $this->variablesProcessorRegistry->expects(self::once())
            ->method('has')
            ->with('computedField_processor')
            ->willReturn(true);
        $computedFieldProcessor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('computedField_processor')
            ->willReturn($computedFieldProcessor);
        $computedFieldProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function ($variable, $definition, TemplateData $data) use ($entity2) {
                $data->setComputedVariable($variable, $entity2);
            });

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    public function testRenderTemplateForSecondLevelComputedAssociationButWhenProcessorDoesNotSetComputedValue()
    {
        $template = '{{ entity.field1.computedField.field11 }}';
        $expectedRenderedResult = $this->getEntityVariableTemplate(
            'field11',
            'entity.field1.computedField.field11',
            'entity.field1.computedField'
        );

        $entity1 = new TestSubEntity1();
        $entity = new TestMainEntity();
        $entity->setField1($entity1);

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity)  => [],
            get_class($entity1) => [
                'computedField' => [
                    'processor' => 'computedField_processor'
                ]
            ]
        ]);

        $this->variablesProcessorRegistry->expects(self::once())
            ->method('has')
            ->with('computedField_processor')
            ->willReturn(true);
        $computedFieldProcessor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('computedField_processor')
            ->willReturn($computedFieldProcessor);
        $computedFieldProcessor->expects(self::once())
            ->method('process');

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRenderTemplateForSeveralComputedFieldsOnDifferentLevels()
    {
        $template = 'test '
            . "\n"
            . '{{ entity.computedField1 }}'
            . "\n"
            . '{{ entity.computedField1.field11 }}'
            . "\n"
            . '{{ entity.field1.computedField2 }}'
            . "\n"
            . '{{ entity.field1.computedField2.field21 }}';
        $expectedRenderedResult = 'test '
            . "\n"
            . $this->getEntityVariableTemplate(
                'computedField1',
                'computed.entity__computedField1',
                'entity'
            )
            . "\n"
            . $this->getEntityVariableTemplate(
                'field11',
                'computed.entity__computedField1.field11',
                'computed.entity__computedField1'
            )
            . "\n"
            . $this->getEntityVariableTemplate(
                'computedField2',
                'computed.entity__field1__computedField2',
                'entity.field1'
            )
            . "\n"
            . $this->getEntityVariableTemplate(
                'field21',
                'computed.entity__field1__computedField2.field21',
                'computed.entity__field1__computedField2'
            );

        $field1Entity = new TestSubEntity3();
        $entity = new TestMainEntity();
        $entity->setField1($field1Entity);
        $entity1 = new TestSubEntity1();
        $entity2 = new TestSubEntity2();

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity)       => [
                'computedField1' => [
                    'processor' => 'computedField1_processor'
                ]
            ],
            get_class($field1Entity) => [
                'computedField2' => [
                    'processor' => 'computedField2_processor'
                ]
            ],
            get_class($entity1)      => [],
            get_class($entity2)      => []
        ]);

        $this->variablesProcessorRegistry->expects(self::exactly(2))
            ->method('has')
            ->willReturnCallback(function ($alias) {
                return in_array(
                    $alias,
                    ['computedField1_processor', 'computedField2_processor'],
                    true
                );
            });
        $computedField1Processor = $this->createMock(VariableProcessorInterface::class);
        $computedField2Processor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['computedField1_processor', $computedField1Processor],
                ['computedField2_processor', $computedField2Processor]
            ]);
        $computedField1Processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function ($variable, $definition, TemplateData $data) use ($entity1) {
                $data->setComputedVariable($variable, $entity1);
            });
        $computedField2Processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function ($variable, $definition, TemplateData $data) use ($entity2) {
                $data->setComputedVariable($variable, $entity2);
            });

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRenderTemplateForSeveralComputedFieldsOnDifferentLevelsReverseOrderOfVariables()
    {
        $template = 'test '
            . "\n"
            . '{{ entity.field1.computedField2.field21 }}'
            . "\n"
            . '{{ entity.field1.computedField2 }}'
            . "\n"
            . '{{ entity.computedField1.field11 }}'
            . "\n"
            . '{{ entity.computedField1 }}';
        $expectedRenderedResult = 'test '
            . "\n"
            . $this->getEntityVariableTemplate(
                'field21',
                'computed.entity__field1__computedField2.field21',
                'computed.entity__field1__computedField2'
            )
            . "\n"
            . $this->getEntityVariableTemplate(
                'computedField2',
                'computed.entity__field1__computedField2',
                'entity.field1'
            )
            . "\n"
            . $this->getEntityVariableTemplate(
                'field11',
                'computed.entity__computedField1.field11',
                'computed.entity__computedField1'
            )
            . "\n"
            . $this->getEntityVariableTemplate(
                'computedField1',
                'computed.entity__computedField1',
                'entity'
            );

        $field1Entity = new TestSubEntity3();
        $entity = new TestMainEntity();
        $entity->setField1($field1Entity);
        $entity1 = new TestSubEntity1();
        $entity2 = new TestSubEntity2();

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity)       => [
                'computedField1' => [
                    'processor' => 'computedField1_processor'
                ]
            ],
            get_class($field1Entity) => [
                'computedField2' => [
                    'processor' => 'computedField2_processor'
                ]
            ],
            get_class($entity1)      => [],
            get_class($entity2)      => []
        ]);

        $this->variablesProcessorRegistry->expects(self::exactly(2))
            ->method('has')
            ->willReturnCallback(function ($alias) {
                return in_array(
                    $alias,
                    ['computedField1_processor', 'computedField2_processor'],
                    true
                );
            });
        $computedField1Processor = $this->createMock(VariableProcessorInterface::class);
        $computedField2Processor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['computedField1_processor', $computedField1Processor],
                ['computedField2_processor', $computedField2Processor]
            ]);
        $computedField1Processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function ($variable, $definition, TemplateData $data) use ($entity1) {
                $data->setComputedVariable($variable, $entity1);
            });
        $computedField2Processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function ($variable, $definition, TemplateData $data) use ($entity2) {
                $data->setComputedVariable($variable, $entity2);
            });

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    public function testRenderTemplateForComputedFieldWithDotInName()
    {
        $template = '{{ entity.computed.field }}';
        $expectedRenderedResult = $this->getEntityVariableTemplate(
            'computedField',
            'computed.entity__computed_field',
            'entity'
        );

        $entity = new TestMainEntity();
        $computedValue = 'testVal';

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity) => [
                'computed.field' => [
                    'processor' => 'computedField_processor'
                ]
            ]
        ]);

        $this->variablesProcessorRegistry->expects(self::once())
            ->method('has')
            ->with('computedField_processor')
            ->willReturn(true);
        $computedFieldProcessor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('computedField_processor')
            ->willReturn($computedFieldProcessor);
        $computedFieldProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function ($variable, $definition, TemplateData $data) use ($computedValue) {
                $data->setComputedVariable($variable, $computedValue);
            });

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    public function testRenderTemplateForComputedFieldWithDotInNameWithinComplexTemplateAndMultipleArrayParameters()
    {
        $template = 'Entity #{{ entity.id }} has been edited ' .
            '{% if transitionUser is defined and transitionUser is not empty %}by {{ transitionUser.firstName }}' .
            '{% endif %} on {{ transitionRecord.transitionDate|oro_format_date_by_entity(entity) }} ' .
            '(<a href="{{ entity.url.view }}">view online</a>). ' .
            '<a href="{{ website_path("oro_sale_quote_frontend_view", { "id": entity.id, ' .
            '"parameter": entity.parameter }, { "website": entity.website }) }}">link</a>';

        $expectedRenderedResult = 'Entity #{% if entity.id is defined %}{{ _entity_var("id", entity.id, entity) }}' .
            '{% else %}{{ "variable_not_found_message" }}{% endif %} has been edited ' .
            '{% if transitionUser is defined and transitionUser is not empty %}by {{ transitionUser.firstName }}' .
            '{% endif %} on {{ transitionRecord.transitionDate|oro_format_date_by_entity(entity) }} ' .
            '(<a href="{% if computed.entity__url_view is defined %}' .
            '{{ _entity_var("urlView", computed.entity__url_view, entity) }}' .
            '{% else %}{{ "variable_not_found_message" }}{% endif %}">view online</a>). ' .
            '<a href="{{ website_path("oro_sale_quote_frontend_view", { "id": entity.id, ' .
            '"parameter": entity.parameter }, { "website": computed.entity__website }) }}">link</a>';

        $entity = new TestMainEntity();
        $computedValue = 'testVal';

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity) => [
                'url.view' => [
                    'processor' => 'computedField_processor'
                ],
                'website' => [
                    'processor' => 'computedField_processor'
                ],
            ]
        ]);

        $this->variablesProcessorRegistry->expects(self::exactly(2))
            ->method('has')
            ->with('computedField_processor')
            ->willReturn(true);
        $computedFieldProcessor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::exactly(2))
            ->method('get')
            ->with('computedField_processor')
            ->willReturn($computedFieldProcessor);
        $computedFieldProcessor->expects(self::exactly(2))
            ->method('process')
            ->willReturnCallback(function ($variable, $definition, TemplateData $data) use ($computedValue) {
                $data->setComputedVariable($variable, $computedValue);
            });

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    public function testRenderTemplateForFirstLevelComputedArray()
    {
        $template = '{{ entity.computedField.field1 }}';
        $expectedRenderedResult = $this->getEntityVariableTemplate(
            'field1',
            'computed.entity__computedField.field1',
            'computed.entity__computedField'
        );

        $entity = new TestMainEntity();
        $computedValue = ['field1' => 'value1'];

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity)  => [
                'computedField' => [
                    'processor' => 'computedField_processor'
                ]
            ]
        ]);

        $this->variablesProcessorRegistry->expects(self::once())
            ->method('has')
            ->with('computedField_processor')
            ->willReturn(true);
        $computedFieldProcessor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('computedField_processor')
            ->willReturn($computedFieldProcessor);
        $computedFieldProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function ($variable, $definition, TemplateData $data) use ($computedValue) {
                $data->setComputedVariable($variable, $computedValue);
            });

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    public function testRenderTemplateForSecondLevelComputedArray()
    {
        $template = '{{ entity.field1.computedField.field11.field111.field1111 }}';
        $expectedRenderedResult = $this->getEntityVariableTemplate(
            'field1111',
            'computed.entity__field1__computedField.field11.field111.field1111',
            'computed.entity__field1__computedField.field11.field111'
        );

        $entity1 = new TestSubEntity1();
        $entity = new TestMainEntity();
        $entity->setField1($entity1);
        $computedValue = ['field11' => ['field111' => []]];

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [],
                'methods'            => [get_class($entity) => ['getField1']],
                'accessors'          => [get_class($entity) => ['field1' => 'getField1']],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity)  => [],
            get_class($entity1) => [
                'computedField' => [
                    'processor' => 'computedField_processor'
                ]
            ]
        ]);

        $this->variablesProcessorRegistry->expects(self::once())
            ->method('has')
            ->with('computedField_processor')
            ->willReturn(true);
        $computedFieldProcessor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('computedField_processor')
            ->willReturn($computedFieldProcessor);
        $computedFieldProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function ($variable, $definition, TemplateData $data) use ($computedValue) {
                $data->setComputedVariable($variable, $computedValue);
            });

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }

    public function testRenderTemplateForSecondLevelComputedFieldWhenParentEntityIsAccessedAsPropertyNotByMethod()
    {
        $template = '{{ entity.field2.computedField }}';
        $expectedRenderedResult = $this->getEntityVariableTemplate(
            'computedField',
            'computed.entity__field2__computedField',
            'entity.field2'
        );

        $entity1 = new TestSubEntity1();
        $entity = new TestMainEntity();
        $entity->field2 = $entity1;
        $computedValue = 'testVal';

        $this->configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([
                'properties'         => [get_class($entity) => ['field2']],
                'methods'            => [],
                'accessors'          => [get_class($entity) => ['field2' => null]],
                'default_formatters' => []
            ]);
        $this->expectVariables([
            get_class($entity)  => [],
            get_class($entity1) => [
                'computedField' => [
                    'processor' => 'computedField_processor'
                ]
            ]
        ]);

        $this->variablesProcessorRegistry->expects(self::once())
            ->method('has')
            ->with('computedField_processor')
            ->willReturn(true);
        $computedFieldProcessor = $this->createMock(VariableProcessorInterface::class);
        $this->variablesProcessorRegistry->expects(self::once())
            ->method('get')
            ->with('computedField_processor')
            ->willReturn($computedFieldProcessor);
        $computedFieldProcessor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function ($variable, $definition, TemplateData $data) use ($computedValue) {
                $data->setComputedVariable($variable, $computedValue);
            });

        $templateStub = new TestTemplateStub($this->environment, '', $expectedRenderedResult);
        $templateWrapper = new TemplateWrapper($this->environment, $templateStub);
        $this->environment->expects(self::once())
            ->method('createTemplate')
            ->with($expectedRenderedResult)
            ->willReturn($templateWrapper);

        $result = $this->renderer->renderTemplate($template, ['entity' => $entity]);
        self::assertSame($expectedRenderedResult, $result);
    }
}
