<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use GuzzleHttp\ClientInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\Type\ExternalFileType;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestSubscriber;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\FileType as SymfonyFileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileTypeTest extends \PHPUnit\Framework\TestCase
{
    private FileType $type;

    protected function setUp(): void
    {
        $externalFileFactory = new ExternalFileFactory($this->createMock(ClientInterface::class));

        $this->type = new FileType($externalFileFactory);
    }

    public function testBuildForm(): void
    {
        $eventSubscriber = new TestSubscriber();
        $this->type->setEventSubscriber($eventSubscriber);
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('addEventSubscriber')
            ->with($eventSubscriber);

        $builder->expects(self::once())
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA, [$this->type, 'preSetData']);

        $options = [
            'checkEmptyFile' => true,
            'addEventSubscriber' => true,
            'fileOptions' => [],
            'isExternalFile' => false,
        ];

        $builder->expects(self::exactly(2))
            ->method('add')
            ->withConsecutive(['file', SymfonyFileType::class, []], ['emptyFile', HiddenType::class]);

        $this->type->buildForm($builder, $options);
    }

    public function testBuildFormWhenExternalFile(): void
    {
        $eventSubscriber = new TestSubscriber();
        $this->type->setEventSubscriber($eventSubscriber);
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('addEventSubscriber')
            ->with($eventSubscriber);

        $builder->expects(self::once())
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA, [$this->type, 'preSetData']);

        $options = [
            'checkEmptyFile' => true,
            'addEventSubscriber' => true,
            'fileOptions' => [],
            'isExternalFile' => true,
        ];

        $childBuilder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('create')
            ->with('file', ExternalFileType::class, [])
            ->willReturn($childBuilder);

        $childBuilder->expects(self::once())
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA, [$this->type, 'filePreSetData']);

        $builder->expects(self::exactly(2))
            ->method('add')
            ->withConsecutive($childBuilder, ['emptyFile', HiddenType::class]);

        $this->type->buildForm($builder, $options);
    }

    public function testBuildFormWithoutEventSubscriber(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects(self::once())
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA, [$this->type, 'preSetData']);

        $builder->expects(self::never())
            ->method('addEventSubscriber');

        $options = [
            'checkEmptyFile' => true,
            'addEventSubscriber' => false,
            'fileOptions' => [],
            'isExternalFile' => false,
        ];

        $builder->expects(self::exactly(2))
            ->method('add')
            ->withConsecutive(['file', SymfonyFileType::class, []], ['emptyFile', HiddenType::class]);

        $this->type->buildForm($builder, $options);
    }

    public function testPreSetDataRemovesOwnerField(): void
    {
        $form = $this->createMock(FormInterface::class);
        $formEvent = new PreSetDataEvent($form, null);

        $form->expects(self::once())
            ->method('remove')
            ->with('owner');

        $this->type->preSetData($formEvent);
    }

    public function testFilePreSetDataSetsExternalFile(): void
    {
        $form = $this->createMock(FormInterface::class);
        $event = new PreSetDataEvent($form, null);
        $parentForm = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('getParent')
            ->willReturn($parentForm);

        $file = (new File())
            ->setExternalUrl('http://example.org/image.png')
            ->setOriginalFilename('original-image.png')
            ->setFileSize(4242)
            ->setMimeType('image/png');

        $parentForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn($file);

        $this->type->filePreSetData($event);

        self::assertEquals(
            new ExternalFile(
                $file->getExternalUrl(),
                $file->getOriginalFilename(),
                $file->getFileSize(),
                $file->getMimeType()
            ),
            $event->getData()
        );
    }

    public function testFilePreSetDataDoesNothingWhenNoExternalUrl(): void
    {
        $form = $this->createMock(FormInterface::class);
        $event = new PreSetDataEvent($form, null);
        $parentForm = $this->createMock(FormInterface::class);
        $form
            ->expects(self::once())
            ->method('getParent')
            ->willReturn($parentForm);

        $parentForm
            ->expects(self::once())
            ->method('getData')
            ->willReturn(new File());

        $this->type->filePreSetData($event);

        self::assertNull($event->getData());
    }

    /**
     * @dataProvider configureOptionsDataProvider
     */
    public function testConfigureOptions(array $options, array $expectedOptions): void
    {
        $resolver = new OptionsResolver();

        $this->type->configureOptions($resolver);

        self::assertEquals($expectedOptions, $resolver->resolve($options));
    }

    public function testFinishView(): void
    {
        $view = new FormView();

        $fileView = new FormView($view);
        $fileView->vars['id'] = 'file_id';

        $emptyFileView = new FormView($view);
        $emptyFileView->vars['id'] = 'empty_file_id';

        $view->children['file'] = $fileView;
        $view->children['emptyFile'] = $emptyFileView;

        $this->type->finishView($view, $this->createMock(FormInterface::class), []);

        self::assertArrayHasKey('attachmentViewOptions', $view->vars);
        self::assertEquals(
            ['fileSelector' => '#' . $fileView->vars['id'], 'emptyFileSelector' => '#' . $emptyFileView->vars['id']],
            $view->vars['attachmentViewOptions']
        );

        self::assertArrayHasKey('label_attr', $view->vars);
        self::assertArrayHasKey('for', $view->vars['label_attr']);
        self::assertEquals($fileView->vars['id'], $view->vars['label_attr']['for']);
    }

    public function configureOptionsDataProvider(): array
    {
        return [
            'default options' => [
                'options' => [],
                'expectedOptions' => [
                    'data_class' => File::class,
                    'checkEmptyFile' => false,
                    'allowDelete' => true,
                    'addEventSubscriber' => true,
                    'fileOptions' => [
                        'required' => false,
                        'label' => 'oro.attachment.file.label',
                    ],
                    'isExternalFile' => false,
                ],
            ],
            'fileOptions.required is true and fileOptions.constraints is NotBlank when checkEmptyFile is true' => [
                'options' => [
                    'checkEmptyFile' => true,
                ],
                'expectedOptions' => [
                    'data_class' => File::class,
                    'checkEmptyFile' => true,
                    'allowDelete' => true,
                    'addEventSubscriber' => true,
                    'fileOptions' => [
                        'required' => true,
                        'constraints' => [new NotBlank()],
                        'label' => 'oro.attachment.file.label',
                    ],
                    'isExternalFile' => false,
                ],
            ],
            'fileOptions are not overridden when set explicitly' => [
                'options' => [
                    'checkEmptyFile' => true,
                    'fileOptions' => [
                        'required' => false,
                        'constraints' => [new NotNull()],
                        'label' => 'custom_label',
                    ],
                    'isExternalFile' => true,
                ],
                'expectedOptions' => [
                    'data_class' => File::class,
                    'checkEmptyFile' => true,
                    'allowDelete' => true,
                    'addEventSubscriber' => true,
                    'fileOptions' => [
                        'required' => false,
                        'constraints' => [new NotNull()],
                        'label' => 'custom_label',
                    ],
                    'isExternalFile' => true,
                ],
            ],
        ];
    }
}
