<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use GuzzleHttp\ClientInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileConstraintFromSystemConfig;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileConstraintFromSystemConfigValidator;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Form\Type\DigitalAssetInDialogType;
use Oro\Bundle\DigitalAssetBundle\Validator\Constraints\DigitalAssetSourceFileMimeTypeValidator;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedPropertyType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizationCollectionTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\HttpFoundation\Type\FormTypeHttpFoundationExtension;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class DigitalAssetInDialogTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    private const SAMPLE_TITLE = 'sample title';

    /** @var DigitalAssetInDialogType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new DigitalAssetInDialogType();

        parent::setUp();
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertEquals('oro_digital_asset_in_dialog', $this->formType->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => DigitalAsset::class,
                    'validation_groups' => ['Default', 'DigitalAssetInDialog'],
                    'is_image_type' => false,
                    'mime_types' => [],
                    'max_file_size' => 0,
                ]
            );

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm(
        array $options,
        string $expectedTooltip,
        string $expectedLabel,
        array $expectedConstraints
    ): void {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [
                    'titles',
                    LocalizedFallbackValueCollectionType::class,
                    [
                        'label' => 'oro.digitalasset.titles.label',
                        'tooltip' => $expectedTooltip,
                        'required' => true,
                        'entry_options' => [
                            'constraints' => [
                                new NotBlank(),
                                new Length(['max' => 255])
                            ]
                        ],
                    ],
                ],
                [
                    'sourceFile',
                    FileType::class,
                    [
                        'label' => $expectedLabel,
                        'required' => true,
                        'allowDelete' => false,
                        'addEventSubscriber' => false,
                        'fileOptions' => [
                            'required' => true,
                            'constraints' => $expectedConstraints,
                        ],
                    ],
                ]
            )
            ->willReturnSelf();

        $this->formType->buildForm($builder, $options);
    }

    public function buildFormDataProvider(): array
    {
        return [
            'not image type' => [
                'options' => [
                    'is_image_type' => false,
                    'mime_types' => ['sample/type'],
                    'max_file_size' => 10,
                ],
                'expectedTooltip' => 'oro.digitalasset.titles.tooltip_file',
                'expectedLabel' => 'oro.digitalasset.dam.dialog.file.label',
                'expectedConstraints' => [
                    new NotBlank(),
                    new FileConstraintFromSystemConfig(
                        [
                            'mimeTypes' => ['sample/type'],
                            'maxSize' => 10,
                        ]
                    ),
                ],
            ],
            'image type' => [
                'options' => [
                    'is_image_type' => true,
                    'mime_types' => [],
                    'max_file_size' => 0,
                ],
                'expectedTooltip' => 'oro.digitalasset.titles.tooltip_image',
                'expectedLabel' => 'oro.digitalasset.dam.dialog.image.label',
                'expectedConstraints' => [
                    new NotBlank(),
                    new FileConstraintFromSystemConfig(['mimeTypes' => [], 'maxSize' => 0]),
                ],
            ],
        ];
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(DigitalAsset $defaultData, array $submittedData, DigitalAsset $expectedData): void
    {
        $form = $this->factory->create(DigitalAssetInDialogType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData->getTitles(), $form->getData()->getTitles());
        $this->assertEquals($expectedData->getSourceFile()->getFile(), $form->getData()->getSourceFile()->getFile());
        $this->assertInstanceOf(\DateTime::class, $form->getData()->getSourceFile()->getUpdatedAt());
    }

    public function submitDataProvider(): array
    {
        $file = new SymfonyFile('sample-path', false);
        $sourceFile = new File();
        $sourceFile->setFile($file);

        return [
            'title is set, source file is uploaded' => [
                'defaultData' => new DigitalAsset(),
                'submittedData' => [
                    'titles' => ['values' => ['default' => self::SAMPLE_TITLE]],
                    'sourceFile' => ['file' => $file],
                ],
                'expectedData' => (new DigitalAsset())
                    ->addTitle((new LocalizedFallbackValue())->setString(self::SAMPLE_TITLE))
                    ->setSourceFile($sourceFile),
            ],
        ];
    }

    public function testSubmitWhenNoFile(): void
    {
        $defaultData = new DigitalAsset();
        $form = $this->factory->create(DigitalAssetInDialogType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit(
            [
                'titles' => ['values' => ['default' => self::SAMPLE_TITLE]],
                'sourceFile' => ['file' => null],
            ]
        );

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->isSynchronized());
        self::assertStringContainsString('This value should not be blank', (string)$form->getErrors(true, false));
    }

    public function testSubmitWhenNoTitle(): void
    {
        $defaultData = new DigitalAsset();
        $form = $this->factory->create(DigitalAssetInDialogType::class, $defaultData, ['is_image_type' => false]);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertEquals($defaultData, $form->getViewData());

        $sourceFile = new File();
        $sourceFile->setFile(new SymfonyFile('sample-path', false));

        $form->submit(
            [
                'titles' => ['values' => ['default' => '']],
                'sourceFile' => ['file' => $sourceFile],
            ]
        );

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->isSynchronized());
        self::assertStringContainsString('This value should not be blank', (string)$form->getErrors(true, false));
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        $doctrine = $this->createMock(ManagerRegistry::class);

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        FileType::class => new FileType(
                            new ExternalFileFactory($this->createMock(ClientInterface::class))
                        ),
                        DigitalAssetInDialogType::class => $this->formType,
                        LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionType(
                            $doctrine
                        ),
                        LocalizedPropertyType::class => new LocalizedPropertyType(),
                        LocalizationCollectionType::class => new LocalizationCollectionTypeStub(),
                    ],
                    []
                ),
                $this->getValidatorExtension(true),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTypeExtensions(): array
    {
        return array_merge(
            parent::getExtensions(),
            [
                new DataBlockExtension(),
                new FormTypeHttpFoundationExtension(),
            ]
        );
    }

    protected function getValidators(): array
    {
        $fileConstraintFromSystemConfigValidator = $this->createMock(FileConstraintFromSystemConfigValidator::class);
        $digitalAssetSourceFileMimeTypeValidator = $this->createMock(DigitalAssetSourceFileMimeTypeValidator::class);

        return [
            NotBlank::class => new NotBlank(),
            FileConstraintFromSystemConfigValidator::class => $fileConstraintFromSystemConfigValidator,
            DigitalAssetSourceFileMimeTypeValidator::class => $digitalAssetSourceFileMimeTypeValidator,
        ];
    }
}
