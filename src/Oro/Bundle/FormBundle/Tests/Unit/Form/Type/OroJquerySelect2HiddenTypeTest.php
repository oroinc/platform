<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroJquerySelect2HiddenTypeTest extends FormIntegrationTestCase
{
    /** @var SearchRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $searchRegistry;

    /** @var ConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $converter;

    /** @var SearchHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $searchHandler;

    /** @var EntityToIdTransformer|\PHPUnit\Framework\MockObject\MockObject */
    private $entityToIdTransformer;

    /** @var OroJquerySelect2HiddenType */
    private $type;

    protected function setUp(): void
    {
        $this->searchRegistry = $this->createMock(SearchRegistry::class);
        $this->converter = $this->createMock(ConverterInterface::class);
        $this->searchHandler = $this->createMock(SearchHandlerInterface::class);
        $this->entityToIdTransformer = $this->createMock(EntityToIdTransformer::class);

        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->createMock(ConfigInterface::class));

        $this->type = $this->getMockBuilder(OroJquerySelect2HiddenType::class)
            ->onlyMethods(['createDefaultTransformer'])
            ->setConstructorArgs([
                $this->createMock(EntityManager::class),
                $this->searchRegistry,
                $configProvider
            ])
            ->getMock();

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return array_merge(parent::getExtensions(), [
            new PreloadedExtension([OroJquerySelect2HiddenType::class => $this->type], []),
            new TestFormExtension()
        ]);
    }

    private function createEntity(int $id): TestEntity
    {
        $entity = new TestEntity();
        $entity->setId($id);

        return $entity;
    }

    public function testBindDataWithAutocompleteAlias()
    {
        $value = '1';
        $entity = $this->createEntity(1);

        $this->searchRegistry->expects($this->exactly(3))
            ->method('getSearchHandler')
            ->with('foo')
            ->willReturn($this->searchHandler);
        $this->searchHandler->expects($this->once())
            ->method('getProperties')
            ->willReturn(['bar', 'baz']);
        $this->searchHandler->expects($this->once())
            ->method('getEntityName')
            ->willReturn(TestEntity::class);
        $this->searchHandler->expects($this->once())
            ->method('convertItem')
            ->with($entity)
            ->willReturn(['id' => 1, 'bar' => 'Bar value', 'baz' => 'Baz value']);
        $this->searchHandler->expects($this->once())
            ->method('convertItem')
            ->with($entity)
            ->willReturn(['id' => 1, 'bar' => 'Bar value', 'baz' => 'Baz value']);

        $this->type->expects($this->once())
            ->method('createDefaultTransformer')
            ->with(TestEntity::class)
            ->willReturn($this->entityToIdTransformer);
        $this->entityToIdTransformer->expects($this->once())
            ->method('transform')
            ->with($this->isNull())
            ->willReturn(null);
        $this->entityToIdTransformer->expects($this->once())
            ->method('reverseTransform')
            ->with($value)
            ->willReturn($entity);

        $form = $this->factory->create(
            OroJquerySelect2HiddenType::class,
            null,
            ['autocomplete_alias' => 'foo']
        );

        $form->submit($value);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($entity, $form->getData());

        $view = $form->createView();
        $this->assertEquals($value, $view->vars['value']);

        $this->assertSame(
            [
                'placeholder'        => 'oro.form.choose_value',
                'allowClear'         => true,
                'minimumInputLength' => 0,
                'autocomplete_alias' => 'foo',
                'properties'         => ['bar', 'baz'],
                'route_name'         => 'oro_form_autocomplete_search',
                'component'          => 'autocomplete',
                'route_parameters'   => []
            ],
            $view->vars['configs']
        );
        $this->assertSame(
            [
                'data-selected-data' => json_encode(
                    ['id' => 1, 'bar' => 'Bar value', 'baz' => 'Baz value'],
                    JSON_THROW_ON_ERROR
                )
            ],
            $view->vars['attr']
        );
    }

    public function testBindDataWithoutAutocompleteAlias()
    {
        $value = '1';
        $entity = $this->createEntity(1);

        $this->converter->expects($this->once())
            ->method('convertItem')
            ->with($entity)
            ->willReturn(['id' => 1, 'bar' => 'Bar value', 'baz' => 'Baz value']);

        $this->type->expects($this->once())
            ->method('createDefaultTransformer')
            ->with(TestEntity::class)
            ->willReturn($this->entityToIdTransformer);
        $this->entityToIdTransformer->expects($this->once())
            ->method('transform')
            ->with($this->isNull())
            ->willReturn(null);
        $this->entityToIdTransformer->expects($this->once())
            ->method('reverseTransform')
            ->with($value)
            ->willReturn($entity);

        $form = $this->factory->create(
            OroJquerySelect2HiddenType::class,
            null,
            [
                'configs'      => [
                    'route_name'       => 'custom_route',
                    'route_parameters' => ['test' => 'hello']
                ],
                'converter'    => $this->converter,
                'entity_class' => TestEntity::class
            ]
        );

        $form->submit($value);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($entity, $form->getData());

        $view = $form->createView();
        $this->assertEquals($value, $view->vars['value']);

        $this->assertSame(
            [
                'placeholder'        => 'oro.form.choose_value',
                'allowClear'         => true,
                'minimumInputLength' => 0,
                'route_name'         => 'custom_route',
                'route_parameters'   => ['test' => 'hello'],
            ],
            $view->vars['configs']
        );
        $this->assertSame(
            [
                'data-selected-data' => json_encode(
                    ['id' => 1, 'bar' => 'Bar value', 'baz' => 'Baz value'],
                    JSON_THROW_ON_ERROR
                )
            ],
            $view->vars['attr']
        );
    }

    public function testCreateWhenNoRouteNameInConfigs()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Option "configs[route_name]" must be set.');

        $this->factory->create(
            OroJquerySelect2HiddenType::class,
            null,
            ['entity_class' => TestEntity::class]
        );
    }

    public function testCreateWhenNoConverter()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The option "converter" must be set.');

        $this->type->expects($this->once())
            ->method('createDefaultTransformer')
            ->with(TestEntity::class)
            ->willReturn($this->entityToIdTransformer);

        $this->factory->create(
            OroJquerySelect2HiddenType::class,
            null,
            [
                'entity_class' => TestEntity::class,
                'configs'      => [
                    'route_name' => 'foo'
                ]
            ]
        );
    }

    public function testCreateWhenConverterIsInvalid()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\FormBundle\Autocomplete\ConverterInterface", "string" given'
        );

        $this->type->expects($this->once())
            ->method('createDefaultTransformer')
            ->with(TestEntity::class)
            ->willReturn($this->entityToIdTransformer);

        $this->factory->create(
            OroJquerySelect2HiddenType::class,
            null,
            [
                'entity_class' => TestEntity::class,
                'converter'    => 'bar',
                'configs'      => [
                    'route_name' => 'foo'
                ]
            ]
        );
    }

    public function testCreateWhenNoEntityClass()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The option "entity_class" must be set.');

        $this->factory->create(
            OroJquerySelect2HiddenType::class,
            null,
            [
                'autocomplete_alias' => null,
                'converter'          => 'getMockConverter',
                'configs'            => [
                    'route_name' => 'foo'
                ]
            ]
        );
    }

    public function testCreateWhenTransformerIsInvalid()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage(sprintf(
            'The option "transformer" must be an instance of "%s".',
            DataTransformerInterface::class
        ));

        $this->factory->create(
            OroJquerySelect2HiddenType::class,
            null,
            [
                'converter'    => $this->converter,
                'entity_class' => 'bar',
                'configs'      => [
                    'route_name' => 'foo'
                ],
                'transformer'  => 'invalid'
            ]
        );
    }

    public function testDefaultFormOptions()
    {
        $this->type->expects($this->once())
            ->method('createDefaultTransformer')
            ->willReturn($this->entityToIdTransformer);

        $form = $this->factory->create(
            OroJquerySelect2HiddenType::class,
            null,
            [
                'converter'    => $this->converter,
                'entity_class' => \stdClass::class,
                'configs'      => ['route_name' => 'custom']
            ]
        );

        $expectedOptions = [
            'error_bubbling' => false
        ];
        foreach ($expectedOptions as $optionName => $optionValue) {
            $this->assertSame($optionValue, $form->getConfig()->getOption($optionName));
        }
    }
}
