<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\AttachmentBundle\Form\Type\MultiFileType;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\MultipleFileConstraintsProvider;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Form\Extension\DigitalAssetManagerExtension;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProviderInterface;
use Oro\Bundle\DigitalAssetBundle\Reflector\FileReflector;
use Oro\Bundle\DigitalAssetBundle\Tests\Unit\Stub\Entity\EntityWithMultiFile;
use Oro\Bundle\DigitalAssetBundle\Tests\Unit\Stub\EventSubscriberStub;
use Oro\Bundle\DigitalAssetBundle\Tests\Unit\Stub\MultiFileFormTypeStub;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class DigitalAssetManagerExtensionTest extends FormIntegrationTestCase
{
    private const SAMPLE_CLASS = 'SampleClass';
    private const SAMPLE_FIELD = 'sampleField';

    /** @var AttachmentEntityConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentEntityConfigProvider;

    /** @var EntityClassNameHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassNameHelper;

    /** @var PreviewMetadataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $previewMetadataProvider;

    /** @var EntityToIdTransformer|\PHPUnit\Framework\MockObject\MockObject */
    private $digitalAssetToIdTransformer;

    /** @var FileReflector|\PHPUnit\Framework\MockObject\MockObject */
    private $fileReflector;

    /** @var DigitalAssetManagerExtension */
    private $extension;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->attachmentEntityConfigProvider = $this->createMock(AttachmentEntityConfigProviderInterface::class);
        $this->entityClassNameHelper = $this->createMock(EntityClassNameHelper::class);
        $this->previewMetadataProvider = $this->createMock(PreviewMetadataProviderInterface::class);
        $this->digitalAssetToIdTransformer = $this->createMock(EntityToIdTransformer::class);
        $this->fileReflector = $this->createMock(FileReflector::class);

        $this->extension = new DigitalAssetManagerExtension(
            $this->attachmentEntityConfigProvider,
            $this->entityClassNameHelper,
            $this->previewMetadataProvider,
            $this->digitalAssetToIdTransformer,
            $this->fileReflector
        );

        $this->form = $this->createMock(FormInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        $fileType = new FileType();
        $fileType->setEventSubscriber(new EventSubscriberStub());

        $multipleFileConstraintsProvider = $this->createMock(MultipleFileConstraintsProvider::class);
        $multiFileType = new MultiFileType(new EventSubscriberStub(), $multipleFileConstraintsProvider);

        return [
            new PreloadedExtension([$fileType, $multiFileType], [])
        ];
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
            ->willReturnCallback(
                function (array $defaults) {
                    $this->assertArrayHasKey('dam_widget_enabled', $defaults);
                    $this->assertTrue($defaults['dam_widget_enabled']);
                    $this->assertArrayHasKey('dam_widget_route', $defaults);
                    $this->assertSame($defaults['dam_widget_route'], 'oro_digital_asset_widget_choose');
                    $this->assertArrayHasKey('dam_widget_parameters', $defaults);
                    $this->assertNull($defaults['dam_widget_parameters']);
                    $this->assertArrayHasKey('validation_groups', $defaults);
                }
            );

        $resolver->expects($this->once())
            ->method('setNormalizer')
            ->with('fileOptions', $this->isType('callable'));

        $this->extension->configureOptions($resolver);
    }

    /**
     * @dataProvider validationGroupsCallbackDataProvider
     */
    public function testValidationGroupsCallbackWhenCheckEmptyFile(
        array $options,
        bool $useDam,
        array $expectedGroups
    ): void {
        $this->form
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig = $this->createMock(FormConfigInterface::class));

        $formConfig
            ->expects($this->once())
            ->method('getOptions')
            ->willReturn($options);

        $entityFieldConfig = $this->mockEntityFieldConfig();

        $entityFieldConfig
            ->expects($this->once())
            ->method('is')
            ->with('use_dam')
            ->willReturn($useDam);

        $this->assertEquals($expectedGroups, $this->extension->validationGroupsCallback($this->form));
    }

    public function validationGroupsCallbackDataProvider(): array
    {
        return [
            'DAM widget enabled, useDam on' => [
                'options' => ['checkEmptyFile' => true, 'dam_widget_enabled' => true],
                'useDam' => true,
                'expectedGroups' => ['Default', 'DamWidgetEnabled'],
            ],
            'DAM widget enabled, useDam off' => [
                'options' => ['checkEmptyFile' => true, 'dam_widget_enabled' => true],
                'useDam' => false,
                'expectedGroups' => ['Default', 'DamWidgetDisabled'],
            ],
        ];
    }

    public function testValidationGroupsCallbackWhenNotCheckEmptyFile(): void
    {
        $this->form
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig = $this->createMock(FormConfigInterface::class));

        $formConfig
            ->expects($this->once())
            ->method('getOptions')
            ->willReturn(['checkEmptyFile' => false]);

        $this->assertEquals(['Default'], $this->extension->validationGroupsCallback($this->form));
    }

    /**
     * @dataProvider normalizeFileOptionsDataProvider
     */
    public function testNormalizeFileOptions(Options $allOptions, array $option, array $expectedOption): void
    {
        $reflectionProperty = new \ReflectionProperty($allOptions, 'locked');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($allOptions, true);

        $this->assertEquals(
            $expectedOption,
            $this->extension->normalizeFileOptions($allOptions, $option)
        );
    }

    public function normalizeFileOptionsDataProvider(): array
    {
        return [
            'empty options' => [
                'allOptions' => (new OptionsResolver())->setDefaults(
                    [
                        'checkEmptyFile' => false,
                    ]
                ),
                'option' => [],
                'expectedOption' => [
                    'required' => false,
                    'label' => 'oro.attachment.file.label',
                ],
            ],
            'required is set' => [
                'allOptions' => (new OptionsResolver())->setDefaults(
                    [
                        'checkEmptyFile' => true,
                    ]
                ),
                'option' => [
                    'required' => false,
                ],
                'expectedOption' => [
                    'required' => false,
                    'label' => 'oro.attachment.file.label',
                    'constraints' => [new NotBlank(['groups' => 'DamWidgetDisabled'])],
                ],
            ],
            'constraints is set' => [
                'allOptions' => (new OptionsResolver())->setDefaults(
                    [
                        'checkEmptyFile' => true,
                    ]
                ),
                'option' => [
                    'constraints' => [],
                ],
                'expectedOption' => [
                    'required' => true,
                    'label' => 'oro.attachment.file.label',
                    'constraints' => [],
                ],
            ],
            'label is set' => [
                'allOptions' => (new OptionsResolver())->setDefaults(
                    [
                        'checkEmptyFile' => false,
                    ]
                ),
                'option' => [
                    'label' => 'sample-label',
                ],
                'expectedOption' => [
                    'required' => false,
                    'label' => 'sample-label',
                ],
            ],
        ];
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder
            ->expects($this->once())
            ->method('add')
            ->with(
                'digitalAsset',
                HiddenType::class,
                [
                    'error_bubbling' => false,
                    'invalid_message' => 'oro.digitalasset.validator.digital_asset.invalid',
                    'auto_initialize' => false,
                    'constraints' => [new NotBlank(['groups' => 'DamWidgetEnabled'])],
                ]
            );

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

    public function buildFormDataProvider(): array
    {
        $commonOptions = [
            'error_bubbling' => false,
            'invalid_message' => 'oro.digitalasset.validator.digital_asset.invalid',
            'auto_initialize' => false,
            'constraints' => [new NotBlank(['groups' => 'DamWidgetEnabled'])],
        ];

        return [
            [
                'options' => [],
                'expectedOptions' => $commonOptions + ['constraints' => []],
            ],
            [
                'options' => ['checkEmptyFile' => false],
                'expectedOptions' => $commonOptions + ['constraints' => []]
            ],
            [
                'options' => ['checkEmptyFile' => true, 'dam_widget_enabled' => true],
                'expectedOptions' => $commonOptions + ['constraints' => [new NotBlank()]],
            ],
            [
                'options' => ['checkEmptyFile' => true, 'dam_widget_enabled' => false],
                'expectedOptions' => $commonOptions + ['constraints' => []]
            ],
        ];
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
        $file = $this->getMockBuilder(File::class)
            ->addMethods(['getDigitalAsset'])
            ->getMock();

        $formEvent = $this->createMock(FormEvent::class);
        $formEvent
            ->expects($this->once())
            ->method('getData')
            ->willReturn($file);

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
        $file = $this->getMockBuilder(File::class)
            ->addMethods(['getDigitalAsset'])
            ->getMock();

        $formEvent = $this->createMock(FormEvent::class);
        $formEvent
            ->expects($this->once())
            ->method('getData')
            ->willReturn($file);

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

        $this->attachmentEntityConfigProvider
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with($entityClass, $fieldName)
            ->willReturn(null);

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

        $this->attachmentEntityConfigProvider
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with(self::SAMPLE_CLASS, self::SAMPLE_FIELD)
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
                'is_valid_digital_asset' => true,
            ],
            $formView->vars['dam_widget']
        );
        $this->assertEquals(['sample1', 'oro_file_with_digital_asset', 'sample2'], $formView->vars['block_prefixes']);
    }

    /**
     * @dataProvider buildViewWhenSubmittedDataProvider
     */
    public function testBuildViewWhenSubmitted(bool $isValidDigitalAsset): void
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

        $this->form
            ->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $this->form
            ->expects($this->once())
            ->method('get')
            ->with('digitalAsset')
            ->willReturn($digitalAssetForm = $this->createMock(FormInterface::class));

        $digitalAssetForm
            ->expects($this->once())
            ->method('isValid')
            ->willReturn($isValidDigitalAsset);

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
                'is_valid_digital_asset' => $isValidDigitalAsset,
            ],
            $formView->vars['dam_widget']
        );
        $this->assertEquals(['sample1', 'oro_file_with_digital_asset', 'sample2'], $formView->vars['block_prefixes']);
    }

    public function buildViewWhenSubmittedDataProvider(): array
    {
        return [
            'valid' => [
                'isValidDigitalAsset' => true,
            ],
            'invalid' => [
                'isValidDigitalAsset' => false,
            ],
        ];
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

        $file
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);

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
                'is_valid_digital_asset' => true,
            ],
            $formView->vars['dam_widget']
        );
        $this->assertEquals(['sample1', 'oro_file_with_digital_asset', 'sample2'], $formView->vars['block_prefixes']);
    }

    /**
     * @dataProvider buildViewWhenImageDataProvider
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
                'is_valid_digital_asset' => true,
            ],
            $formView->vars['dam_widget']
        );
        $this->assertEquals(['sample1', 'oro_file_with_digital_asset', 'sample2'], $formView->vars['block_prefixes']);
    }

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
            [
                'fieldType' => 'multiImage',
                'isImageType' => true,
            ],
        ];
    }

    public function testBuildViewWhenParentIsLineItem(): void
    {
        $fileItem = (new FileItem())
            ->setFile(new File());

        $entity = new EntityWithMultiFile();
        $entity->multiFileField->add($fileItem);

        $form = $this->factory->create(MultiFileFormTypeStub::class, $entity);

        $fileForm = $form->get('multiFileField')->get(0)->get('file');

        $this->attachmentEntityConfigProvider->expects($this->once())
            ->method('getFieldConfig')
            ->with(EntityWithMultiFile::class, 'multiFileField')
            ->willReturn($entityFieldConfig = $this->createMock(ConfigInterface::class));

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

        $formView = new FormView();
        $formView->vars['block_prefixes'] = ['sample1', 'sample2'];
        $this->extension->buildView(
            $formView,
            $fileForm,
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
                'is_image_type' => true,
                'route' => $options['dam_widget_route'],
                'parameters' => $options['dam_widget_parameters'],
                'is_valid_digital_asset' => true,
            ],
            $formView->vars['dam_widget']
        );
        $this->assertEquals(['sample1', 'oro_file_with_digital_asset', 'sample2'], $formView->vars['block_prefixes']);
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

        $fieldConfigId
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn(self::SAMPLE_CLASS);

        $fieldConfigId
            ->expects($this->once())
            ->method('getFieldName')
            ->willReturn(self::SAMPLE_FIELD);

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
                'is_valid_digital_asset' => true,
            ],
            $formView->vars['dam_widget']
        );
        $this->assertEquals(['sample1', 'oro_file_with_digital_asset', 'sample2'], $formView->vars['block_prefixes']);
    }
}
