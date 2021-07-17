<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestSubscriber;
use Symfony\Component\Form\Extension\Core\Type\FileType as SymfonyFileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new FileType();
    }

    public function testBuildForm(): void
    {
        $event = new TestSubscriber();
        $this->type->setEventSubscriber($event);
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($event);

        $builder->expects($this->exactly(2))
            ->method('addEventListener')
            ->withConsecutive(
                [FormEvents::PRE_SET_DATA, $this->isType('array')],
                [FormEvents::POST_SUBMIT, $this->isType('array')]
            );

        $options = [
            'checkEmptyFile' => true,
            'addEventSubscriber' => true,
            'fileOptions' => $fileOptions = [],
        ];

        $builder->expects($this->once())
            ->method('add')
            ->with('file', SymfonyFileType::class, $fileOptions);

        $this->type->buildForm($builder, $options);
    }

    public function testBuildFormWithoutEventSubscriber(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->exactly(2))
            ->method('addEventListener')
            ->withConsecutive(
                [FormEvents::PRE_SET_DATA, $this->isType('array')],
                [FormEvents::POST_SUBMIT, $this->isType('array')]
            );

        $options = [
            'checkEmptyFile' => true,
            'addEventSubscriber' => false,
            'fileOptions' => $fileOptions = [],
        ];

        $builder->expects($this->once())
            ->method('add')
            ->with('file', SymfonyFileType::class, $fileOptions);

        $this->type->buildForm($builder, $options);
    }

    public function testPreSetDataWhenNotAllowDelete(): void
    {
        $formEvent = $this->createMock(FormEvent::class);

        $formEvent->expects($this->once())
            ->method('getForm')
            ->willReturn($form = $this->createMock(FormInterface::class));

        $form->expects($this->once())
            ->method('remove')
            ->with('owner');

        $form->method('getConfig')
            ->willReturn($formConfig = $this->createMock(FormConfigInterface::class));

        $formConfig->method('getOption')
            ->with('allowDelete')
            ->willReturn(false);

        $form->expects($this->never())
            ->method('add');

        $this->type->preSetData($formEvent);
    }

    public function testPreSetDataWhenAllowDelete(): void
    {
        $formEvent = $this->createMock(FormEvent::class);

        $formEvent->expects($this->once())
            ->method('getForm')
            ->willReturn($form = $this->createMock(FormInterface::class));

        $form->expects($this->once())
            ->method('remove')
            ->with('owner');

        $form->method('getConfig')
            ->willReturn($formConfig = $this->createMock(FormConfigInterface::class));

        $formConfig->method('getOption')
            ->with('allowDelete')
            ->willReturn(true);

        $form->expects($this->once())
            ->method('add')
            ->with('emptyFile', HiddenType::class, ['required' => false]);

        $this->type->preSetData($formEvent);
    }

    public function testPostSubmitWhenNoEntity(): void
    {
        $formEvent = $this->createMock(FormEvent::class);

        $formEvent->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $this->type->postSubmit($formEvent);
    }

    public function testPostSubmitWhenNoFile(): void
    {
        $formEvent = $this->createMock(FormEvent::class);

        $formEvent->expects($this->once())
            ->method('getData')
            ->willReturn($file = $this->createMock(File::class));

        $file
            ->expects($this->never())
            ->method('setUpdatedAt');

        $this->type->postSubmit($formEvent);
    }

    public function testPostSubmitWhenEmptyFile(): void
    {
        $formEvent = $this->createMock(FormEvent::class);

        $formEvent->expects($this->once())
            ->method('getData')
            ->willReturn($file = $this->createMock(File::class));

        $file
            ->expects($this->once())
            ->method('isEmptyFile')
            ->willReturn(true);

        $file
            ->expects($this->once())
            ->method('setUpdatedAt')
            ->with($this->isInstanceOf(\DateTime::class));

        $this->type->postSubmit($formEvent);
    }

    public function testPostSubmitWhenUploadedFile(): void
    {
        $formEvent = $this->createMock(FormEvent::class);

        $formEvent->expects($this->once())
            ->method('getData')
            ->willReturn($file = $this->createMock(File::class));

        $file
            ->expects($this->once())
            ->method('getFile')
            ->willReturn($this->createMock(ComponentFile::class));

        $file
            ->expects($this->once())
            ->method('setUpdatedAt')
            ->with($this->isInstanceOf(\DateTime::class));

        $this->type->postSubmit($formEvent);
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => File::class,
                    'checkEmptyFile' => false,
                    'allowDelete' => true,
                    'addEventSubscriber' => true,
                    'fileOptions' => [],
                ]
            );

        $resolver->expects($this->once())
            ->method('setAllowedTypes')
            ->with('fileOptions', 'array');

        $resolver->expects($this->once())
            ->method('setNormalizer')
            ->with('fileOptions', $this->isType('callable'));

        $this->type->configureOptions($resolver);
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
            $this->type->normalizeFileOptions($allOptions, $option)
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
                    'constraints' => [new NotBlank()],
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
}
