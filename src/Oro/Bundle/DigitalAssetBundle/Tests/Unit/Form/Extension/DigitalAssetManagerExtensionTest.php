<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Form\Extension\DigitalAssetManagerExtension;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProvider;
use Oro\Bundle\DigitalAssetBundle\Reflector\FileReflector;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DigitalAssetManagerExtensionTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private const SAMPLE_CLASS = 'SampleClass';
    private const SAMPLE_FIELD = 'sampleField';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigManager;

    /** @var EntityClassNameHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassNameHelper;

    /** @var PreviewMetadataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $previewMetadataProvider;

    /** @var EntityToIdTransformer|\PHPUnit\Framework\MockObject\MockObject */
    private $digitalAssetToIdTransformer;

    /** @var FileReflector|\PHPUnit\Framework\MockObject\MockObject */
    private $fileReflector;

    /** @var DigitalAssetManagerExtension */
    private $extension;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    protected function setUp()
    {
        $this->entityConfigManager = $this->createMock(ConfigManager::class);
        $this->entityClassNameHelper = $this->createMock(EntityClassNameHelper::class);
        $this->previewMetadataProvider = $this->createMock(PreviewMetadataProvider::class);
        $this->digitalAssetToIdTransformer = $this->createMock(EntityToIdTransformer::class);
        $this->fileReflector = $this->createMock(FileReflector::class);

        $this->extension = new DigitalAssetManagerExtension(
            $this->entityConfigManager,
            $this->entityClassNameHelper,
            $this->previewMetadataProvider,
            $this->digitalAssetToIdTransformer,
            $this->fileReflector
        );

        $this->form = $this->createMock(FormInterface::class);

        $this->setUpLoggerMock($this->extension);
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([FileType::class, ImageType::class], DigitalAssetManagerExtension::getExtendedTypes());
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver
            ->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'dam_widget_enabled' => true,
                    'dam_widget_route' => 'oro_digital_asset_widget_choose',
                    'dam_widget_parameters' => null,
                ]
            );

        $this->extension->configureOptions($resolver);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder
            ->expects($this->once())
            ->method('add')
            ->with('digitalAsset', HiddenType::class);

        $builder
            ->expects($this->once())
            ->method('get')
            ->with('digitalAsset')
            ->willReturn($digitalAssetForm = $this->createMock(FormBuilderInterface::class));

        $digitalAssetForm
            ->expects($this->once())
            ->method('addModelTransformer')
            ->with($this->digitalAssetToIdTransformer);

        $builder
            ->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, $this->isType('array'));

        $this->extension->buildForm($builder, []);
    }

    public function testPostSubmitWhenNoFile(): void
    {
        $formEvent = $this->createMock(FormEvent::class);
        $formEvent
            ->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $this->fileReflector
            ->expects($this->never())
            ->method('reflectFromDigitalAsset');

        $this->extension->postSubmit($formEvent);
    }

    public function testPostSubmitWhenNoDigitalAsset(): void
    {
        $formEvent = $this->createMock(FormEvent::class);
        $formEvent
            ->expects($this->once())
            ->method('getData')
            ->willReturn($file = $this->createPartialMock(File::class, ['getDigitalAsset']));

        $file
            ->expects($this->once())
            ->method('getDigitalAsset')
            ->willReturn(null);

        $this->fileReflector
            ->expects($this->never())
            ->method('reflectFromDigitalAsset');

        $this->extension->postSubmit($formEvent);
    }

    public function testPostSubmit(): void
    {
        $formEvent = $this->createMock(FormEvent::class);
        $formEvent
            ->expects($this->once())
            ->method('getData')
            ->willReturn($file = $this->createPartialMock(File::class, ['getDigitalAsset']));

        $file
            ->expects($this->once())
            ->method('getDigitalAsset')
            ->willReturn($digitalAsset = $this->createMock(DigitalAsset::class));

        $this->fileReflector
            ->expects($this->once())
            ->method('reflectFromDigitalAsset')
            ->with($file, $digitalAsset);

        $this->extension->postSubmit($formEvent);
    }

    public function testBuildViewWhenDamWidgetIsAlreadyAdded(): void
    {
        $formView = new FormView();
        $formView->vars['dam_widget'] = [];

        $this->extension->buildView(
            $formView,
            $this->createMock(FormInterface::class),
            []
        );

        $this->assertArrayNotHasKey('route', $formView->vars['dam_widget']);
    }

    public function testBuildViewWhenDamDisabled(): void
    {
        $this->extension->buildView(
            $formView = new FormView(),
            $this->form,
            ['dam_widget_enabled' => false]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    public function testBuildViewWhenNoPropertyPath(): void
    {
        $this->form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn(null);

        $this->extension->buildView(
            $formView = new FormView(),
            $this->form,
            ['dam_widget_enabled' => true]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    public function testBuildViewWhenMultiplePropertyPath(): void
    {
        $this->form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn($propertyPath = $this->createMock(PropertyPathInterface::class));

        $propertyPath
            ->expects($this->once())
            ->method('getLength')
            ->willReturn(2);

        $this->extension->buildView(
            $formView = new FormView(),
            $this->form,
            ['dam_widget_enabled' => true]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    public function testBuildViewWhenNoParent(): void
    {
        $this->form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn($propertyPath = $this->createMock(PropertyPathInterface::class));

        $propertyPath
            ->expects($this->once())
            ->method('getLength')
            ->willReturn(1);

        $this->form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn(null);

        $this->extension->buildView(
            $formView = new FormView(),
            $this->form,
            ['dam_widget_enabled' => true]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    public function testBuildViewWhenNoParentDataClass(): void
    {
        $this->form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn($propertyPath = $this->createMock(PropertyPathInterface::class));

        $propertyPath
            ->expects($this->once())
            ->method('getLength')
            ->willReturn(1);

        $this->form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm = $this->createMock(FormInterface::class));

        $parentForm
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($parentFormConfig = $this->createMock(FormConfigInterface::class));

        $parentFormConfig
            ->expects($this->once())
            ->method('getDataClass')
            ->willReturn(null);

        $this->extension->buildView(
            $formView = new FormView(),
            $this->form,
            ['dam_widget_enabled' => true]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    public function testBuildViewWhenNoFieldName(): void
    {
        $this->form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn($propertyPath = $this->createMock(PropertyPathInterface::class));

        $propertyPath
            ->expects($this->once())
            ->method('getLength')
            ->willReturn(1);

        $this->form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm = $this->createMock(FormInterface::class));

        $parentForm
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($parentFormConfig = $this->createMock(FormConfigInterface::class));

        $parentFormConfig
            ->expects($this->once())
            ->method('getDataClass')
            ->willReturn(self::SAMPLE_CLASS);

        $propertyPath
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('');

        $this->extension->buildView(
            $formView = new FormView(),
            $this->form,
            ['dam_widget_enabled' => true]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    public function testBuildViewWhenNoFieldConfig(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn($propertyPath = $this->createMock(PropertyPathInterface::class));

        $propertyPath
            ->expects($this->once())
            ->method('getLength')
            ->willReturn(1);

        $form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm = $this->createMock(FormInterface::class));

        $parentForm
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($parentFormConfig = $this->createMock(FormConfigInterface::class));

        $parentFormConfig
            ->expects($this->once())
            ->method('getDataClass')
            ->willReturn($entityClass = 'SampleClass');

        $propertyPath
            ->expects($this->once())
            ->method('__toString')
            ->willReturn($fieldName = 'sampleField');

        $this->entityConfigManager
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with('attachment', $entityClass, $fieldName)
            ->willThrowException(new RuntimeException());

        $this->assertLoggerWarningMethodCalled();

        $this->extension->buildView(
            $formView = new FormView(),
            $form,
            ['dam_widget_enabled' => true]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }


    public function testBuildViewWhenDamDisabledInConfig(): void
    {
        $entityFieldConfig = $this->mockEntityFieldConfig();

        $entityFieldConfig
            ->expects($this->once())
            ->method('is')
            ->with('use_dam')
            ->willReturn(false);

        $this->extension->buildView(
            $formView = new FormView(),
            $this->form,
            ['dam_widget_enabled' => true]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    /**
     * @return ConfigInterface
     */
    private function mockEntityFieldConfig(): ConfigInterface
    {
        $this->form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn($propertyPath = $this->createMock(PropertyPathInterface::class));

        $propertyPath
            ->expects($this->once())
            ->method('getLength')
            ->willReturn(1);

        $this->form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm = $this->createMock(FormInterface::class));

        $parentForm
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($parentFormConfig = $this->createMock(FormConfigInterface::class));

        $parentFormConfig
            ->expects($this->once())
            ->method('getDataClass')
            ->willReturn(self::SAMPLE_CLASS);

        $propertyPath
            ->expects($this->once())
            ->method('__toString')
            ->willReturn(self::SAMPLE_FIELD);

        $this->entityConfigManager
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with('attachment', self::SAMPLE_CLASS, self::SAMPLE_FIELD)
            ->willReturn($entityFieldConfig = $this->createMock(ConfigInterface::class));

        return $entityFieldConfig;
    }

    public function testBuildViewWhenNoPreviewMetadata(): void
    {
        $entityFieldConfig = $this->mockEntityFieldConfig();

        $entityFieldConfig
            ->expects($this->once())
            ->method('is')
            ->with('use_dam')
            ->willReturn(true);

        $entityFieldConfig
            ->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId = $this->createMock(FieldConfigId::class));

        $fieldConfigId
            ->expects($this->once())
            ->method('getFieldType')
            ->willReturn('sampleType');

        $this->form
            ->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $formView = new FormView();
        $formView->vars['block_prefixes'] = ['sample1', 'sample2'];
        $this->extension->buildView(
            $formView,
            $this->form,
            $options = [
                'dam_widget_enabled' => true,
                'dam_widget_route' => 'sample_route',
                'dam_widget_parameters' => ['sample_params'],
            ]
        );

        $this->assertArrayHasKey('dam_widget', $formView->vars);
        $this->assertEquals(
            [
                'preview_metadata' => [],
                'is_image_type' => false,
                'route' => $options['dam_widget_route'],
                'parameters' => $options['dam_widget_parameters'],
            ],
            $formView->vars['dam_widget']
        );
        $this->assertEquals(['sample1', 'oro_file_with_digital_asset', 'sample2'], $formView->vars['block_prefixes']);
    }

    public function testBuildViewWhenPreviewMetadata(): void
    {
        $entityFieldConfig = $this->mockEntityFieldConfig();

        $entityFieldConfig
            ->expects($this->once())
            ->method('is')
            ->with('use_dam')
            ->willReturn(true);

        $entityFieldConfig
            ->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId = $this->createMock(FieldConfigId::class));

        $fieldConfigId
            ->expects($this->once())
            ->method('getFieldType')
            ->willReturn('image');

        $this->form
            ->expects($this->once())
            ->method('getData')
            ->willReturn($file = $this->createMock(File::class));

        $this->previewMetadataProvider
            ->expects($this->once())
            ->method('getMetadata')
            ->with($file)
            ->willReturn($previewMetadata = ['sample' => 'metadata']);

        $formView = new FormView();
        $formView->vars['block_prefixes'] = ['sample1', 'sample2'];
        $this->extension->buildView(
            $formView,
            $this->form,
            $options = [
                'dam_widget_enabled' => true,
                'dam_widget_route' => 'sample_route',
                'dam_widget_parameters' => ['sample_params'],
            ]
        );

        $this->assertArrayHasKey('dam_widget', $formView->vars);
        $this->assertEquals(
            [
                'preview_metadata' => $previewMetadata,
                'is_image_type' => true,
                'route' => $options['dam_widget_route'],
                'parameters' => $options['dam_widget_parameters'],
            ],
            $formView->vars['dam_widget']
        );
        $this->assertEquals(['sample1', 'oro_file_with_digital_asset', 'sample2'], $formView->vars['block_prefixes']);
    }

    /**
     * @dataProvider buildViewWhenImageDataProvider
     *
     * @param string $fieldType
     * @param bool $isImageType
     */
    public function testBuildViewWhenImage(string $fieldType, bool $isImageType): void
    {
        $entityFieldConfig = $this->mockEntityFieldConfig();

        $entityFieldConfig
            ->expects($this->once())
            ->method('is')
            ->with('use_dam')
            ->willReturn(true);

        $entityFieldConfig
            ->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId = $this->createMock(FieldConfigId::class));

        $fieldConfigId
            ->expects($this->once())
            ->method('getFieldType')
            ->willReturn($fieldType);

        $this->form
            ->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $formView = new FormView();
        $formView->vars['block_prefixes'] = ['sample1', 'sample2'];
        $this->extension->buildView(
            $formView,
            $this->form,
            $options = [
                'dam_widget_enabled' => true,
                'dam_widget_route' => 'sample_route',
                'dam_widget_parameters' => ['sample_params'],
            ]
        );

        $this->assertArrayHasKey('dam_widget', $formView->vars);
        $this->assertEquals(
            [
                'preview_metadata' => [],
                'is_image_type' => $isImageType,
                'route' => $options['dam_widget_route'],
                'parameters' => $options['dam_widget_parameters'],
            ],
            $formView->vars['dam_widget']
        );
        $this->assertEquals(['sample1', 'oro_file_with_digital_asset', 'sample2'], $formView->vars['block_prefixes']);
    }

    /**
     * @return array
     */
    public function buildViewWhenImageDataProvider(): array
    {
        return [
            [
                'fieldType' => self::SAMPLE_FIELD,
                'isImageType' => false,
            ],
            [
                'fieldType' => 'image',
                'isImageType' => true,
            ],
        ];
    }

    public function testBuildViewDefaultRouteParameters(): void
    {
        $entityFieldConfig = $this->mockEntityFieldConfig();

        $entityFieldConfig
            ->expects($this->once())
            ->method('is')
            ->with('use_dam')
            ->willReturn(true);

        $entityFieldConfig
            ->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId = $this->createMock(FieldConfigId::class));

        $fieldConfigId
            ->expects($this->once())
            ->method('getFieldType')
            ->willReturn('image');

        $this->form
            ->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $this->entityClassNameHelper
            ->expects($this->once())
            ->method('getUrlSafeClassName')
            ->with(self::SAMPLE_CLASS)
            ->willReturn($safeEntityClass = 'SafeSampleClass');

        $formView = new FormView();
        $formView->vars['block_prefixes'] = ['sample1', 'sample2'];
        $this->extension->buildView(
            $formView,
            $this->form,
            $options = [
                'dam_widget_enabled' => true,
                'dam_widget_route' => 'sample_route',
                'dam_widget_parameters' => null,
            ]
        );

        $this->assertArrayHasKey('dam_widget', $formView->vars);
        $this->assertEquals(
            [
                'preview_metadata' => [],
                'is_image_type' => true,
                'route' => $options['dam_widget_route'],
                'parameters' => [
                    'parentEntityClass' => $safeEntityClass,
                    'parentEntityFieldName' => self::SAMPLE_FIELD,
                ],
            ],
            $formView->vars['dam_widget']
        );
        $this->assertEquals(['sample1', 'oro_file_with_digital_asset', 'sample2'], $formView->vars['block_prefixes']);
    }
}
