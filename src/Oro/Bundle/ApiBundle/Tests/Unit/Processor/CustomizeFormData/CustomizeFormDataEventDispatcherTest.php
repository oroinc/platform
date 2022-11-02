<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataEventDispatcher;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataHandler;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class CustomizeFormDataEventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_EVENT_NAME = 'test_customize_form_data_event';

    /** @var CustomizeFormDataHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $customizationHandler;

    /** @var CustomizeFormDataEventDispatcher */
    private $eventDispatcher;

    protected function setUp(): void
    {
        $this->customizationHandler = $this->createMock(CustomizeFormDataHandler::class);

        $this->eventDispatcher = new CustomizeFormDataEventDispatcher(
            $this->customizationHandler
        );
    }

    private function getForm(string $name, bool $compound = false, bool $hasApiEventContext = false): FormInterface
    {
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects(self::any())
            ->method('getName')
            ->willReturn($name);
        $formConfig->expects(self::any())
            ->method('getCompound')
            ->willReturn($compound);
        $formConfig->expects(self::any())
            ->method('getDataMapper')
            ->willReturn($compound ? $this->createMock(DataMapperInterface::class) : null);
        $formConfig->expects(self::any())
            ->method('getInheritData')
            ->willReturn(false);
        $formConfig->expects(self::any())
            ->method('getAutoInitialize')
            ->willReturn(false);
        $formConfig->expects(self::any())
            ->method('hasAttribute')
            ->with(CustomizeFormDataHandler::API_EVENT_CONTEXT)
            ->willReturn($hasApiEventContext);

        return new Form($formConfig);
    }

    private function markFormAsSubmitted(FormInterface $form): void
    {
        FormUtil::markAsSubmitted($form);
    }

    private function setFormViewData(FormInterface $form, $data): void
    {
        ReflectionUtil::setPropertyValue($form, 'defaultDataSet', true);
        ReflectionUtil::setPropertyValue($form, 'viewData', $data);
    }

    public function testDispatchWithCompoundChildWithApiEventContext(): void
    {
        $childEntity = $this->createMock(\stdClass::class);
        $childEntityForm = $this->getForm('compound1', true, true);
        $this->setFormViewData($childEntityForm, $childEntity);

        $entity = $this->createMock(\stdClass::class);
        $form = $this->getForm('root', true, true);
        $form->add($this->getForm('field1'));
        $form->add($childEntityForm);
        $this->setFormViewData($form, $entity);
        $this->markFormAsSubmitted($form);

        $this->customizationHandler->expects(self::exactly(2))
            ->method('handleFormEvent')
            ->withConsecutive(
                [
                    self::TEST_EVENT_NAME,
                    self::callback(function (FormEvent $event) use ($childEntityForm, $childEntity) {
                        self::assertSame($childEntityForm, $event->getForm(), 'Unexpected included entity form');
                        self::assertSame($childEntity, $event->getData(), 'Unexpected included entity');

                        return true;
                    })
                ],
                [
                    self::TEST_EVENT_NAME,
                    self::callback(function (FormEvent $event) use ($form, $entity) {
                        self::assertSame($form, $event->getForm(), 'Unexpected primary entity form');
                        self::assertSame($entity, $event->getData(), 'Unexpected primary entity');

                        return true;
                    })
                ]
            );

        $this->eventDispatcher->dispatch(self::TEST_EVENT_NAME, $form);
    }

    public function testDispatchWithoutCompoundChild(): void
    {
        $entity = $this->createMock(\stdClass::class);
        $form = $this->getForm('root', true, true);
        $form->add($this->getForm('field1'));
        $this->setFormViewData($form, $entity);
        $this->markFormAsSubmitted($form);

        $this->customizationHandler->expects(self::once())
            ->method('handleFormEvent')
            ->with(
                self::TEST_EVENT_NAME,
                self::callback(function (FormEvent $event) use ($form, $entity) {
                    self::assertSame($form, $event->getForm(), 'Unexpected primary entity form');
                    self::assertSame($entity, $event->getData(), 'Unexpected primary entity');

                    return true;
                })
            );

        $this->eventDispatcher->dispatch(self::TEST_EVENT_NAME, $form);
    }

    public function testDispatchWhenPrimaryEntityFormDoesNotHaveApiEventContext(): void
    {
        $entity = $this->createMock(\stdClass::class);
        $form = $this->getForm('root', true);
        $form->add($this->getForm('field1'));
        $this->setFormViewData($form, $entity);
        $this->markFormAsSubmitted($form);

        $this->customizationHandler->expects(self::never())
            ->method('handleFormEvent');

        $this->eventDispatcher->dispatch(self::TEST_EVENT_NAME, $form);
    }

    public function testDispatchWhenCompoundChildDoesNotHaveApiEventContext(): void
    {
        $childEntity = $this->createMock(\stdClass::class);
        $childEntityForm = $this->getForm('compound1', true);
        $this->setFormViewData($childEntityForm, $childEntity);

        $entity = $this->createMock(\stdClass::class);
        $form = $this->getForm('root', true, true);
        $form->add($this->getForm('field1'));
        $form->add($childEntityForm);
        $this->setFormViewData($form, $entity);
        $this->markFormAsSubmitted($form);

        $this->customizationHandler->expects(self::once())
            ->method('handleFormEvent')
            ->with(
                self::TEST_EVENT_NAME,
                self::callback(function (FormEvent $event) use ($form, $entity) {
                    self::assertSame($form, $event->getForm(), 'Unexpected primary entity form');
                    self::assertSame($entity, $event->getData(), 'Unexpected primary entity');

                    return true;
                })
            );

        $this->eventDispatcher->dispatch(self::TEST_EVENT_NAME, $form);
    }
}
