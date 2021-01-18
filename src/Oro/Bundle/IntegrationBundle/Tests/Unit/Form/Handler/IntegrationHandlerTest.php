<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Event\DefaultOwnerSetEvent;
use Oro\Bundle\IntegrationBundle\Event\IntegrationUpdateEvent;
use Oro\Bundle\IntegrationBundle\Form\Handler\ChannelHandler as IntegrationHandler;
use Oro\Bundle\IntegrationBundle\Form\Type\ChannelType;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class IntegrationHandlerTest extends \PHPUnit\Framework\TestCase
{
    const FORM_NAME = 'form_name';
    const FORM_DATA = ['field' => 'data'];

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface */
    private $form;

    /** @var Request */
    private $request;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManager */
    private $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    private $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormFactoryInterface */
    private $formFactory;

    /** @var IntegrationHandler */
    private $handler;

    /** @var Integration */
    private $entity;

    protected function setUp(): void
    {
        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->form = $this->createMock(Form::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->entity = new Integration();
        $this->handler = new IntegrationHandler(
            $requestStack,
            $this->form,
            $this->em,
            $this->eventDispatcher,
            $this->formFactory
        );
    }

    public function testProcessUnsupportedRequest()
    {
        $this->form->expects($this->once())->method('setData')
            ->with($this->entity);

        $this->form->expects($this->never())->method('submit');

        $this->assertFalse($this->handler->process($this->entity));
    }

    /**
     * @dataProvider supportedMethods
     *
     * @param string $method
     */
    public function testProcessSupportedRequest($method)
    {
        $this->request->initialize([], [self::FORM_NAME => self::FORM_DATA]);
        $this->request->setMethod($method);

        $this->form->expects($this->atLeastOnce())->method('getName')->willReturn(self::FORM_NAME);
        $this->form->expects($this->once())->method('setData')
            ->with($this->entity);
        $this->form->expects($this->once())->method('submit')
            ->with(self::FORM_DATA);

        $this->assertFalse($this->handler->process($this->entity));
    }

    /**
     * @return array
     */
    public function supportedMethods()
    {
        return [['POST', 'PUT']];
    }

    public function testProcessValidData()
    {
        $this->request->initialize([], [self::FORM_NAME => self::FORM_DATA]);
        $this->request->setMethod('POST');

        $this->form->expects($this->atLeastOnce())->method('getName')->willReturn(self::FORM_NAME);
        $this->form->expects($this->once())->method('setData')->with($this->entity);
        $this->form->expects($this->once())->method('submit')->with(self::FORM_DATA);
        $this->form->expects($this->once())->method('isValid')
            ->will($this->returnValue(true));

        $this->em->expects($this->once())->method('persist')->with($this->entity);
        $this->em->expects($this->once())->method('flush');

        $this->assertTrue($this->handler->process($this->entity));
    }

    /**
     * @dataProvider eventDataProvider
     *
     * @param Integration      $entity
     * @param null|User        $newOwner
     * @param Integration|null $existingIntegration
     * @param bool             $expectOwnerSetEvent
     * @param bool             $expectIntegrationUpdateEvent
     */
    public function testEventDispatching(
        $entity,
        $newOwner,
        $existingIntegration,
        $expectOwnerSetEvent,
        $expectIntegrationUpdateEvent
    ) {
        $this->request->initialize([], [self::FORM_NAME => self::FORM_DATA]);
        $this->request->setMethod('POST');

        $this->form->expects($this->any())->method('getName')->willReturn(self::FORM_NAME);
        $this->form->expects($this->once())->method('setData')->with($entity)
            ->will(
                $this->returnCallback(
                    function ($entity) use ($newOwner) {
                        $entity->setDefaultUserOwner($newOwner);
                    }
                )
            );
        $this->form->expects($this->once())->method('submit')->with(self::FORM_DATA);
        $this->form->expects($this->once())->method('isValid')
            ->will($this->returnValue(true));

        $this->em->expects($this->once())->method('persist')->with($entity);
        $this->em->expects($this->once())->method('flush');

        if ($entity->getId()) {
            $this->em->expects($this->once())
                ->method('find')
                ->with('OroIntegrationBundle:Channel', $entity->getId())
                ->will($this->returnValue($existingIntegration));
        }

        $dispatchCallIndex = 0;
        if ($expectOwnerSetEvent) {
            $this->eventDispatcher->expects($this->at($dispatchCallIndex++))
                ->method('dispatch')
                ->with(
                    $this->isInstanceOf(DefaultOwnerSetEvent::class),
                    $this->equalTo(DefaultOwnerSetEvent::NAME)
                );
        }
        if ($expectIntegrationUpdateEvent) {
            $this->eventDispatcher->expects($this->at($dispatchCallIndex++))
                ->method('dispatch')
                ->with(
                    $this->callback(
                        function ($event) use ($entity, $existingIntegration) {
                            $this->assertInstanceOf(IntegrationUpdateEvent::class, $event);

                            $this->assertSame($entity, $event->getIntegration());
                            $this->assertEquals($existingIntegration, $event->getOldState());

                            return true;
                        }
                    ),
                    $this->equalTo(IntegrationUpdateEvent::NAME)
                );
        } elseif (!$expectOwnerSetEvent) {
            $this->eventDispatcher->expects($this->never())->method('dispatch');
        }

        $this->assertTrue($this->handler->process($entity));
    }

    /**
     * @return array
     */
    public function eventDataProvider()
    {
        $newIntegration = new Integration();
        $newOwner = $this->createMock(User::class);

        $idProperty = new \ReflectionProperty(Integration::class, 'id');
        $idProperty->setAccessible(true);

        $existingIntegration = new Integration();
        $idProperty->setValue($existingIntegration, 100);

        $someOwner = $this->createMock(User::class);
        $existingIntegrationWithOwner = clone $existingIntegration;
        $existingIntegrationWithOwner->setDefaultUserOwner($someOwner);

        $integration = new Integration();
        $idProperty->setValue($integration, 200);
        return [
            'new entity, should not dispatch' => [
                $newIntegration,
                $newOwner,
                $integration,
                false,
                false
            ],
            'integration is not new, but owner existed before' => [
                $existingIntegrationWithOwner,
                $newOwner,
                $integration,
                false,
                true
            ],
            'existing integration without owner, should dispatch' => [
                $existingIntegration,
                $newOwner,
                $integration,
                true,
                false
            ],
            'should not dispatch if integration not found' => [
                $existingIntegrationWithOwner,
                $newOwner,
                null,
                false,
                false
            ]
        ];
    }

    public function testGetForm()
    {
        self::assertSame($this->form, $this->handler->getForm());
    }

    public function testUpdateForm()
    {
        $this->request->initialize([], [
            self::FORM_NAME => self::FORM_DATA,
            IntegrationHandler::UPDATE_MARKER => true,
        ]);
        $this->request->setMethod('POST');

        $this->formFactory->expects(self::once())
            ->method('createNamed')
            ->with(
                self::FORM_NAME,
                ChannelType::class,
                $this->entity
            )
            ->willReturn($this->form);

        $this->form->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn(self::FORM_NAME);

        $this->form->expects(self::once())
            ->method('submit')
            ->with(self::FORM_DATA);

        $this->em->expects(self::never())
            ->method('persist');

        $this->em->expects(self::never())
            ->method('flush');

        $this->handler->process($this->entity);
    }

    public function testUpdateFormOnTransportTypeChanged()
    {
        $this->request->initialize([], [
            self::FORM_NAME => self::FORM_DATA,
            IntegrationHandler::UPDATE_MARKER => sprintf(
                '%s[%s]',
                self::FORM_NAME,
                IntegrationHandler::TRANSPORT_TYPE_FIELD_NAME
            ),
        ]);
        $this->request->setMethod('POST');

        $this->formFactory->expects(self::once())
            ->method('createNamed')
            ->with(
                self::FORM_NAME,
                ChannelType::class,
                $this->entity
            )
            ->willReturn($this->form);

        $this->form->expects(self::atLeastOnce())
            ->method('getName')
            ->willReturn(self::FORM_NAME);

        $this->form->expects(self::exactly(2))
            ->method('submit')
            ->with(self::FORM_DATA);

        $this->em->expects(self::never())
            ->method('persist');

        $this->em->expects(self::never())
            ->method('flush');

        $this->handler->process($this->entity);
    }
}
