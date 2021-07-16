<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\EventSubscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Form\EventSubscriber\FileSubscriber;
use Oro\Bundle\AttachmentBundle\Form\EventSubscriber\MultipleFileSubscriber;
use Oro\Bundle\AttachmentBundle\Form\Type\MultiFileType;
use Oro\Bundle\AttachmentBundle\Validator\ConfigMultipleFileValidator;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class MultipleFileSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigMultipleFileValidator|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var MultipleFileSubscriber */
    private $subscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ConfigMultipleFileValidator::class);

        $this->validator
            ->expects(self::never())
            ->method('validateImages');

        $this->form = $this->createMock(FormInterface::class);

        $this->form
            ->method('getConfig')
            ->willReturn($formConfig = $this->createMock(FormConfigInterface::class));

        $formConfig
            ->method('getType')
            ->willReturn($formType = $this->createMock(ResolvedFormTypeInterface::class));

        $formType
            ->method('getInnerType')
            ->willReturn($this->createMock(MultiFileType::class));

        $this->subscriber = new MultipleFileSubscriber($this->validator);
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
        $formEvent = $this->mockFormEvent(null);

        $this->validator
            ->expects(self::never())
            ->method('validateFiles');

        $this->subscriber->postSubmit($formEvent);
    }

    private function mockFormEvent(?Collection $data): FormEvent
    {
        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $formEvent */
        $formEvent = $this->createMock(FormEvent::class);
        $formEvent
            ->expects(self::once())
            ->method('getData')
            ->willReturn($data);

        $formEvent
            ->expects(self::once())
            ->method('getForm')
            ->willReturn($this->form);

        return $formEvent;
    }

    public function testPostSubmitWhenEmptyFile(): void
    {
        $formEvent = $this->mockFormEvent(new ArrayCollection());

        $this->validator
            ->expects(self::never())
            ->method('validateFiles');

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenParentEntityClass(): void
    {
        $files = new ArrayCollection();
        $files->add(new FileItem());

        $formEvent = $this->mockFormEvent($files);

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
            ->method('validateFiles')
            ->with($files, $dataClass, '')
            ->willReturn($violationsList = $this->createMock(ConstraintViolationList::class));

        $violationsList
            ->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenParentFormDataClass(): void
    {
        $files = new ArrayCollection();
        $files->add(new FileItem());

        $formEvent = $this->mockFormEvent($files);

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
            ->method('validateFiles')
            ->with($files, $dataClass, $fieldName)
            ->willReturn($violationsList = $this->createMock(ConstraintViolationList::class));

        $violationsList
            ->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenParentParentFormDataClass(): void
    {
        $files = new ArrayCollection();
        $files->add(new FileItem());

        $formEvent = $this->mockFormEvent($files);

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
            ->method('validateFiles')
            ->with($files, $dataClass, $fieldName)
            ->willReturn($violationsList = $this->createMock(ConstraintViolationList::class));

        $violationsList
            ->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->subscriber->postSubmit($formEvent);
    }

    public function testPostSubmitWhenViolationsNotEmpty(): void
    {
        $files = new ArrayCollection();
        $files->add(new FileItem());

        $formEvent = $this->mockFormEvent($files);

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
                $files,
                'files',
                'sample-invalid-value'
            ),
        ];

        $this->validator
            ->expects(self::once())
            ->method('validateFiles')
            ->with($files, $dataClass, '')
            ->willReturn($violationsList = new ConstraintViolationList($violations));

        $this->form
            ->expects(self::once())
            ->method('addError')
            ->withConsecutive(
                [new FormError($message1, $messageTemplate1, $parameters1)]
            );

        $this->subscriber->postSubmit($formEvent);
    }
}
