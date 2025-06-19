<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Form\EventSubscriber\FileSubscriber;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class FileSubscriberTest extends TestCase
{
    private ConfigFileValidator&MockObject $validator;
    private FormInterface&MockObject $form;
    private FormConfigInterface&MockObject $formConfig;
    private FileSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ConfigFileValidator::class);
        $this->form = $this->createMock(FormInterface::class);
        $this->formConfig = $this->createMock(FormConfigInterface::class);
        $this->form->expects(self::any())
            ->method('getConfig')
            ->willReturn($this->formConfig);

        $this->subscriber = new FileSubscriber($this->validator);
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                FormEvents::POST_SUBMIT => 'postSubmit'
            ],
            FileSubscriber::getSubscribedEvents()
        );
    }

    public function testPostSubmitWhenNoFile(): void
    {
        $formEvent = new FormEvent($this->form, null);

        $this->validator->expects(self::never())
            ->method('validate');

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenEmptyFile(): void
    {
        $formEvent = new FormEvent($this->form, new File());

        $this->validator->expects(self::never())
            ->method('validate');

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenParentEntityClass(): void
    {
        $formEvent = new FormEvent($this->form, $file = new File());

        $file->setFile($componentFile = $this->createMock(ComponentFile::class));

        $parentForm = $this->createMock(FormInterface::class);
        $this->form->expects(self::atLeastOnce())
            ->method('getParent')
            ->willReturn($parentForm);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $parentForm->expects(self::atLeastOnce())
            ->method('getConfig')
            ->willReturn($formConfig);

        $dataClass = \stdClass::class;
        $formConfig->expects(self::atLeastOnce())
            ->method('getOption')
            ->with('parentEntityClass', null)
            ->willReturn($dataClass);

        $violationsList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with($componentFile, $dataClass, '')
            ->willReturn($violationsList);
        $violationsList->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenParentFormDataClass(): void
    {
        $formEvent = new FormEvent($this->form, $file = new File());

        $file->setFile($componentFile = $this->createMock(ComponentFile::class));

        $fieldName = 'sampleField';
        $this->form->expects(self::once())
            ->method('getName')
            ->willReturn($fieldName);

        $parentForm = $this->createMock(FormInterface::class);
        $this->form->expects(self::atLeastOnce())
            ->method('getParent')
            ->willReturn($parentForm);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $parentForm->expects(self::atLeastOnce())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig->expects(self::once())
            ->method('getOption')
            ->with('parentEntityClass', null)
            ->willReturn(null);

        $dataClass = \stdClass::class;
        $formConfig->expects(self::once())
            ->method('getDataClass')
            ->willReturn($dataClass);

        $violationsList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with($componentFile, $dataClass, $fieldName)
            ->willReturn($violationsList);
        $violationsList->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenParentParentFormDataClass(): void
    {
        $formEvent = new FormEvent($this->form, $file = new File());

        $file->setFile($componentFile = $this->createMock(ComponentFile::class));

        $fieldName = 'sampleField';
        $this->form->expects(self::once())
            ->method('getName')
            ->willReturn($fieldName);

        $parentForm = $this->createMock(FormInterface::class);
        $this->form->expects(self::atLeastOnce())
            ->method('getParent')
            ->willReturn($parentForm);

        $parentFormConfig = $this->createMock(FormConfigInterface::class);
        $parentForm->expects(self::atLeastOnce())
            ->method('getConfig')
            ->willReturn($parentFormConfig);

        $parentFormConfig->expects(self::once())
            ->method('getOption')
            ->with('parentEntityClass', null)
            ->willReturn(null);

        $parentFormConfig->expects(self::once())
            ->method('getDataClass')
            ->willReturn(null);

        $parentParentForm = $this->createMock(FormInterface::class);
        $parentForm->expects(self::once())
            ->method('getParent')
            ->willReturn($parentParentForm);

        $parentParentFormConfig = $this->createMock(FormConfigInterface::class);
        $parentParentForm->expects(self::once())
            ->method('getConfig')
            ->willReturn($parentParentFormConfig);

        $dataClass = \stdClass::class;
        $parentParentFormConfig->expects(self::once())
            ->method('getDataClass')
            ->willReturn($dataClass);

        $violationsList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with($componentFile, $dataClass, $fieldName)
            ->willReturn($violationsList);
        $violationsList->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenDataClassIsFileItem(): void
    {
        $formEvent = new FormEvent($this->form, $file = new File());

        $file->setFile($componentFile = $this->createMock(ComponentFile::class));

        $this->form->expects(self::once())
            ->method('getName')
            ->willReturn('sampleField');

        $parentForm = $this->createMock(FormInterface::class);
        $this->form->expects(self::atLeastOnce())
            ->method('getParent')
            ->willReturn($parentForm);

        $parentFormConfig = $this->createMock(FormConfigInterface::class);
        $parentForm->expects(self::atLeastOnce())
            ->method('getConfig')
            ->willReturn($parentFormConfig);

        $parentFormConfig->expects(self::once())
            ->method('getOption')
            ->with('parentEntityClass', null)
            ->willReturn(null);

        $parentFormConfig->expects(self::once())
            ->method('getDataClass')
            ->willReturn(null);

        $parentParentForm = $this->createMock(FormInterface::class);
        $parentForm->expects(self::atLeastOnce())
            ->method('getParent')
            ->willReturn($parentParentForm);

        $parentParentFormConfig = $this->createMock(FormConfigInterface::class);
        $parentParentForm->expects(self::once())
            ->method('getConfig')
            ->willReturn($parentParentFormConfig);

        $parentParentFormConfig->expects(self::once())
            ->method('getDataClass')
            ->willReturn(FileItem::class);

        $parentParentParentForm = $this->createMock(FormInterface::class);
        $parentParentForm->expects(self::once())
            ->method('getParent')
            ->willReturn($parentParentParentForm);

        $parentParentParentFormConfig = $this->createMock(FormConfigInterface::class);
        $parentParentParentForm->expects(self::once())
            ->method('getConfig')
            ->willReturn($parentParentParentFormConfig);

        $parentParentParentFormConfig->expects(self::once())
            ->method('getDataClass')
            ->willReturn(\stdClass::class);

        $parentParentForm->expects(self::once())
            ->method('getPropertyPath')
            ->willReturn(new PropertyPath('fileItemField'));

        $violationsList = $this->createMock(ConstraintViolationList::class);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with($componentFile, \stdClass::class, 'fileItemField')
            ->willReturn($violationsList);
        $violationsList->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenViolationsNotEmpty(): void
    {
        $formEvent = new FormEvent($this->form, $file = new File());

        $file->setFile($componentFile = $this->createMock(ComponentFile::class));

        $parentForm = $this->createMock(FormInterface::class);
        $this->form->expects(self::atLeastOnce())
            ->method('getParent')
            ->willReturn($parentForm);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $parentForm->expects(self::atLeastOnce())
            ->method('getConfig')
            ->willReturn($formConfig);

        $dataClass = \stdClass::class;
        $formConfig->expects(self::atLeastOnce())
            ->method('getOption')
            ->with('parentEntityClass', null)
            ->willReturn($dataClass);

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

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($componentFile, $dataClass, '')
            ->willReturn(new ConstraintViolationList($violations));

        $fileForm = $this->createMock(FormInterface::class);
        $this->form->expects(self::once())
            ->method('get')
            ->with('file')
            ->willReturn($fileForm);

        $fileForm->expects(self::exactly(2))
            ->method('addError')
            ->withConsecutive(
                [new FormError($message1, $messageTemplate1, $parameters1)],
                [new FormError($message2, $messageTemplate2, $parameters2)]
            );

        $this->subscriber->postSubmit($formEvent);
    }
}
