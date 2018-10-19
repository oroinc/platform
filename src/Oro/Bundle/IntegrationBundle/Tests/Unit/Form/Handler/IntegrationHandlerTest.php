<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Event\DefaultOwnerSetEvent;
use Oro\Bundle\IntegrationBundle\Event\IntegrationUpdateEvent;
use Oro\Bundle\IntegrationBundle\Form\Handler\ChannelHandler as IntegrationHandler;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class IntegrationHandlerTest extends \PHPUnit\Framework\TestCase
{
    const FORM_NAME = 'form_name';
    const FORM_DATA = ['field' => 'data'];

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManager */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var IntegrationHandler */
    protected $handler;

    /** @var Integration */
    protected $entity;

    protected function setUp()
    {
        $this->request         = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->form            = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()->getMock();
        $this->em              = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->entity  = new Integration();
        $this->handler = new IntegrationHandler($requestStack, $this->form, $this->em, $this->eventDispatcher);
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

        $this->form->expects($this->any())->method('getName')->willReturn(self::FORM_NAME);
        $this->form->expects($this->once()) ->method('setData')
            ->with($this->entity);
        $this->form->expects($this->once()) ->method('submit')
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

        $this->form->expects($this->any())->method('getName')->willReturn(self::FORM_NAME);
        $this->form->expects($this->once())->method('setData')->with($this->entity);
        $this->form->expects($this->once()) ->method('submit') ->with(self::FORM_DATA);
        $this->form->expects($this->once()) ->method('isValid')
            ->will($this->returnValue(true));

        $this->em->expects($this->once()) ->method('persist') ->with($this->entity);
        $this->em->expects($this->once())->method('flush');

        $this->assertTrue($this->handler->process($this->entity));
    }

    /**
     * @dataProvider eventDataProvider
     *
     * @param Integration      $entity
     * @param null|User        $newOwner
     * @param Integration|null $oldIntegration
     * @param bool             $expectOwnerSetEvent
     * @param bool             $expectIntegrationUpdateEvent
     */
    public function testEventDispatching(
        $entity,
        $newOwner,
        $oldIntegration,
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
        $this->form->expects($this->once()) ->method('submit') ->with(self::FORM_DATA);
        $this->form->expects($this->once()) ->method('isValid')
            ->will($this->returnValue(true));

        $this->em->expects($this->once()) ->method('persist') ->with($entity);
        $this->em->expects($this->once())->method('flush');

        if ($entity->getId()) {
            $this->em->expects($this->once())
                ->method('find')
                ->with('OroIntegrationBundle:Channel', $entity->getId())
                ->will($this->returnValue($oldIntegration));
        }

        $dispatchCallIndex = 0;
        if ($expectOwnerSetEvent) {
            $this->eventDispatcher->expects($this->at($dispatchCallIndex++))
                ->method('dispatch')
                ->with(
                    $this->equalTo(DefaultOwnerSetEvent::NAME),
                    $this->isInstanceOf('Oro\Bundle\IntegrationBundle\Event\DefaultOwnerSetEvent')
                );
        }
        if ($expectIntegrationUpdateEvent) {
            $this->eventDispatcher->expects($this->at($dispatchCallIndex++))
                ->method('dispatch')
                ->with(
                    $this->equalTo(IntegrationUpdateEvent::NAME),
                    $this->callback(
                        function ($event) use ($entity, $oldIntegration) {
                            $this->assertInstanceOf(
                                'Oro\Bundle\IntegrationBundle\Event\IntegrationUpdateEvent',
                                $event
                            );

                            $this->assertSame($entity, $event->getIntegration());
                            $this->assertEquals($oldIntegration, $event->getOldState());

                            return true;
                        }
                    )
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
        $newOwner   = $this->createMock('Oro\Bundle\UserBundle\Entity\User');

        $idProperty = new \ReflectionProperty('Oro\Bundle\IntegrationBundle\Entity\Channel', 'id');
        $idProperty->setAccessible(true);

        $oldIntegration = new Integration();
        $idProperty->setValue($oldIntegration, 100);

        $someOwner           = $this->createMock('Oro\Bundle\UserBundle\Entity\User');
        $oldIntegrationWithOwner = clone $oldIntegration;
        $oldIntegrationWithOwner->setDefaultUserOwner($someOwner);

        $integration = new Integration();
        $idProperty->setValue($integration, 200);
        return [
            'new entity, should not dispatch'                                             => [
                $newIntegration,
                $newOwner,
                $integration,
                false,
                false
            ],
            'integration is not new, but owner existed before'                            => [
                $oldIntegrationWithOwner,
                $newOwner,
                $integration,
                false,
                true
            ],
            'old integration without owner, should dispatch'                              => [
                $oldIntegration,
                $newOwner,
                $integration,
                true,
                false
            ],
            'should not dispatch if integration not found' => [
                $oldIntegrationWithOwner,
                $newOwner,
                null,
                false,
                false
            ]
        ];
    }
}
