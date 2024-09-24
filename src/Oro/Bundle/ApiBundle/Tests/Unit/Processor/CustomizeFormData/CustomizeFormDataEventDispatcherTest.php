<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataEventDispatcher;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataHandler;
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

    #[\Override]
    protected function setUp(): void
    {
        $this->customizationHandler = $this->createMock(CustomizeFormDataHandler::class);

        $this->eventDispatcher = new CustomizeFormDataEventDispatcher($this->customizationHandler);
    }

    private function getApiEventContext(mixed $data): CustomizeFormDataContext
    {
        $apiEventContext = $this->createMock(CustomizeFormDataContext::class);
        $apiEventContext->expects(self::any())
            ->method('getData')
            ->willReturn($data);

        return $apiEventContext;
    }

    private function getForm(
        string $name,
        bool $compound = false,
        ?CustomizeFormDataContext $apiEventContext = null
    ): FormInterface {
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
            ->method('getAttribute')
            ->with(CustomizeFormDataHandler::API_EVENT_CONTEXT)
            ->willReturn($apiEventContext);

        return new Form($formConfig);
    }

    public function testDispatchWithCompoundChildWithApiEventContext(): void
    {
        $childEntity = $this->createMock(\stdClass::class);
        $childEntityForm = $this->getForm('compound1', true, $this->getApiEventContext($childEntity));

        $entity = $this->createMock(\stdClass::class);
        $form = $this->getForm('root', true, $this->getApiEventContext($entity));
        $form->add($this->getForm('field1'));
        $form->add($childEntityForm);
        FormUtil::markAsSubmitted($form);

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
        $form = $this->getForm('root', true, $this->getApiEventContext($entity));
        $form->add($this->getForm('field1'));
        FormUtil::markAsSubmitted($form);

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
        $form = $this->getForm('root', true);
        $form->add($this->getForm('field1'));
        FormUtil::markAsSubmitted($form);

        $this->customizationHandler->expects(self::never())
            ->method('handleFormEvent');

        $this->eventDispatcher->dispatch(self::TEST_EVENT_NAME, $form);
    }

    public function testDispatchWhenCompoundChildDoesNotHaveApiEventContext(): void
    {
        $childEntityForm = $this->getForm('compound1', true);

        $entity = $this->createMock(\stdClass::class);
        $form = $this->getForm('root', true, $this->getApiEventContext($entity));
        $form->add($this->getForm('field1'));
        $form->add($childEntityForm);
        FormUtil::markAsSubmitted($form);

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
