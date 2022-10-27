<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Form\EventSubscriber\FileSubscriber;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class FileSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private ConfigFileValidator|\PHPUnit\Framework\MockObject\MockObject $validator;

    private FormInterface|\PHPUnit\Framework\MockObject\MockObject $form;

    private FormConfigInterface|\PHPUnit\Framework\MockObject\MockObject $formConfig;

    private FileSubscriber $subscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ConfigFileValidator::class);
        $this->form = $this->createMock(FormInterface::class);
        $this->formConfig = $this->createMock(FormConfigInterface::class);
        $this->form
            ->expects(self::any())
            ->method('getConfig')
            ->willReturn($this->formConfig);

        $this->subscriber = new FileSubscriber($this->validator);
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                FormEvents::POST_SUBMIT => 'postSubmit',
            ],
            FileSubscriber::getSubscribedEvents()
        );
    }

    public function testPostSubmitWhenNoFile(): void
    {
        $formEvent = new FormEvent($this->form, null);

        $this->validator
            ->expects(self::never())
            ->method('validate');

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenEmptyFile(): void
    {
        $formEvent = new FormEvent($this->form, new File());

        $this->validator
            ->expects(self::never())
            ->method('validate');

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenParentEntityClass(): void
    {
        $formEvent = new FormEvent($this->form, $file = new File());

        $file->setFile($componentFile = $this->createMock(ComponentFile::class));

        $this->form
            ->method('getParent')
            ->willReturn($parentForm = $this->createMock(FormInterface::class));

        $parentForm
            ->method('getConfig')
            ->willReturn($formConfig = $this->createMock(FormConfigInterface::class));

        $formConfig
            ->method('getOption')
            ->with('parentEntityClass', null)
            ->willReturn($dataClass = \stdClass::class);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($componentFile, $dataClass, '')
            ->willReturn($violationsList = $this->createMock(ConstraintViolationList::class));

        $violationsList
            ->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenParentFormDataClass(): void
    {
        $formEvent = new FormEvent($this->form, $file = new File());

        $file->setFile($componentFile = $this->createMock(ComponentFile::class));

        $this->form
            ->method('getName')
            ->willReturn($fieldName = 'sampleField');

        $this->form
            ->method('getParent')
            ->willReturn($parentForm = $this->createMock(FormInterface::class));

        $parentForm
            ->method('getConfig')
            ->willReturn($formConfig = $this->createMock(FormConfigInterface::class));

        $formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('parentEntityClass', null)
            ->willReturn(null);

        $formConfig
            ->method('getDataClass')
            ->willReturn($dataClass = \stdClass::class);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($componentFile, $dataClass, $fieldName)
            ->willReturn($violationsList = $this->createMock(ConstraintViolationList::class));

        $violationsList
            ->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenParentParentFormDataClass(): void
    {
        $formEvent = new FormEvent($this->form, $file = new File());

        $file->setFile($componentFile = $this->createMock(ComponentFile::class));

        $this->form
            ->method('getName')
            ->willReturn($fieldName = 'sampleField');

        $this->form
            ->method('getParent')
            ->willReturn($parentForm = $this->createMock(FormInterface::class));

        $parentForm
            ->method('getConfig')
            ->willReturn($parentFormConfig = $this->createMock(FormConfigInterface::class));

        $parentFormConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('parentEntityClass', null)
            ->willReturn(null);

        $parentFormConfig
            ->method('getDataClass')
            ->willReturn(null);

        $parentForm
            ->method('getParent')
            ->willReturn($parentParentForm = $this->createMock(FormInterface::class));

        $parentParentForm
            ->method('getConfig')
            ->willReturn($parentParentFormConfig = $this->createMock(FormConfigInterface::class));

        $parentParentFormConfig
            ->method('getDataClass')
            ->willReturn($dataClass = \stdClass::class);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($componentFile, $dataClass, $fieldName)
            ->willReturn($violationsList = $this->createMock(ConstraintViolationList::class));

        $violationsList
            ->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenDataClassIsFileItem(): void
    {
        $formEvent = new FormEvent($this->form, $file = new File());

        $file->setFile($componentFile = $this->createMock(ComponentFile::class));

        $this->form
            ->method('getName')
            ->willReturn('sampleField');

        $this->form
            ->method('getParent')
            ->willReturn($parentForm = $this->createMock(FormInterface::class));

        $parentForm
            ->method('getConfig')
            ->willReturn($parentFormConfig = $this->createMock(FormConfigInterface::class));

        $parentFormConfig
            ->expects(self::once())
            ->method('getOption')
            ->with('parentEntityClass', null)
            ->willReturn(null);

        $parentFormConfig
            ->method('getDataClass')
            ->willReturn(null);

        $parentForm
            ->method('getParent')
            ->willReturn($parentParentForm = $this->createMock(FormInterface::class));

        $parentParentForm
            ->method('getConfig')
            ->willReturn($parentParentFormConfig = $this->createMock(FormConfigInterface::class));

        $parentParentFormConfig
            ->method('getDataClass')
            ->willReturn(FileItem::class);

        $parentParentForm
            ->method('getParent')
            ->willReturn($parentParentParentForm = $this->createMock(FormInterface::class));

        $parentParentParentForm
            ->method('getConfig')
            ->willReturn($parentParentParentFormConfig = $this->createMock(FormConfigInterface::class));

        $parentParentParentFormConfig
            ->method('getDataClass')
            ->willReturn(\stdClass::class);

        $parentParentForm
            ->method('getPropertyPath')
            ->willReturn(new PropertyPath('fileItemField'));

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($componentFile, \stdClass::class, 'fileItemField')
            ->willReturn($violationsList = $this->createMock(ConstraintViolationList::class));

        $violationsList
            ->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenViolationsNotEmpty(): void
    {
        $formEvent = new FormEvent($this->form, $file = new File());

        $file->setFile($componentFile = $this->createMock(ComponentFile::class));

        $this->form
            ->method('getParent')
            ->willReturn($parentForm = $this->createMock(FormInterface::class));

        $parentForm
            ->method('getConfig')
            ->willReturn($formConfig = $this->createMock(FormConfigInterface::class));

        $formConfig
            ->method('getOption')
            ->with('parentEntityClass', null)
            ->willReturn($dataClass = \stdClass::class);

        $violations = [
            new ConstraintViolation(
                $message1 = 'sample msg1',
                $messageTemplate1 = 'sample msg tpl1',
                $parameters1 = ['sample-parameter1'],
                $componentFile,
                'file',
                'sample-invalid-value'
            ),
            new ConstraintViolation(
                $message2 = 'sample msg1',
                $messageTemplate2 = 'sample msg tpl1',
                $parameters2 = ['sample-parameter2'],
                $componentFile,
                'file',
                'sample-invalid-value'
            ),
        ];

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($componentFile, $dataClass, '')
            ->willReturn(new ConstraintViolationList($violations));

        $this->form
            ->expects(self::once())
            ->method('get')
            ->with('file')
            ->willReturn($fileForm = $this->createMock(FormInterface::class));

        $fileForm
            ->expects(self::exactly(2))
            ->method('addError')
            ->withConsecutive(
                [new FormError($message1, $messageTemplate1, $parameters1)],
                [new FormError($message2, $messageTemplate2, $parameters2)]
            );

        $this->subscriber->postSubmit($formEvent);
    }
}
