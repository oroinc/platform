<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\EventSubscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Form\EventSubscriber\FileSubscriber;
use Oro\Bundle\AttachmentBundle\Form\EventSubscriber\MultipleFileSubscriber;
use Oro\Bundle\AttachmentBundle\Form\Type\MultiFileType;
use Oro\Bundle\AttachmentBundle\Validator\ConfigMultipleFileValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class MultipleFileSubscriberTest extends TestCase
{
    private ConfigMultipleFileValidator&MockObject $validator;
    private FormInterface&MockObject $form;
    private MultipleFileSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ConfigMultipleFileValidator::class);

        $this->validator->expects(self::never())
            ->method('validateImages');

        $this->form = $this->createMock(FormInterface::class);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $this->form->expects(self::any())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formType = $this->createMock(ResolvedFormTypeInterface::class);
        $formConfig->expects(self::any())
            ->method('getType')
            ->willReturn($formType);

        $formType->expects(self::any())
            ->method('getInnerType')
            ->willReturn($this->createMock(MultiFileType::class));

        $this->subscriber = new MultipleFileSubscriber($this->validator);
    }

    private function getFormEvent(?Collection $data): FormEvent
    {
        $formEvent = $this->createMock(FormEvent::class);
        $formEvent->expects(self::any())
            ->method('getData')
            ->willReturn($data);
        $formEvent->expects(self::any())
            ->method('getForm')
            ->willReturn($this->form);

        return $formEvent;
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

    public function testPostSubmitWhenNoFiles(): void
    {
        $formEvent = $this->getFormEvent(null);

        $this->validator->expects(self::never())
            ->method('validateFiles');

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenEmptyFile(): void
    {
        $formEvent = $this->getFormEvent(new ArrayCollection());

        $this->validator->expects(self::never())
            ->method('validateFiles');

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenParentEntityClass(): void
    {
        $files = new ArrayCollection();
        $files->add(new FileItem());

        $formEvent = $this->getFormEvent($files);

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
            ->method('validateFiles')
            ->with($files, $dataClass, '')
            ->willReturn($violationsList);
        $violationsList->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenParentFormDataClass(): void
    {
        $files = new ArrayCollection();
        $files->add(new FileItem());

        $formEvent = $this->getFormEvent($files);

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
            ->method('validateFiles')
            ->with($files, $dataClass, $fieldName)
            ->willReturn($violationsList);
        $violationsList->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenParentParentFormDataClass(): void
    {
        $files = new ArrayCollection();
        $files->add(new FileItem());

        $formEvent = $this->getFormEvent($files);

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
            ->method('validateFiles')
            ->with($files, $dataClass, $fieldName)
            ->willReturn($violationsList);
        $violationsList->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenViolationsNotEmpty(): void
    {
        $files = new ArrayCollection();
        $files->add(new FileItem());

        $formEvent = $this->getFormEvent($files);

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
                $files,
                'files',
                'sample-invalid-value'
            ),
        ];

        $this->validator->expects(self::once())
            ->method('validateFiles')
            ->with($files, $dataClass, '')
            ->willReturn(new ConstraintViolationList($violations));

        $this->form->expects(self::once())
            ->method('addError')
            ->withConsecutive(
                [new FormError($message1, $messageTemplate1, $parameters1)]
            );

        $this->subscriber->postSubmit($formEvent);
    }
}
