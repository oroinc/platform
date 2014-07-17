<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Form\Handler\ChannelHandler as IntegrationHandler;
use Oro\Bundle\IntegrationBundle\Event\DefaultOwnerSetEvent;

class IntegrationHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $syncSettings;

    /** @var IntegrationHandler */
    protected $handler;

    /** @var Integration */
    protected $entity;

    protected function setUp()
    {
        $this->request         = new Request();
        $this->form            = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()->getMock();
        $this->em              = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $this->syncSettings = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Common\Object')
            ->disableOriginalConstructor()
            ->getMock();
        $channelRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $channelRepository->expects($this->any())
            ->method('find')
            ->will($this->returnValue($channel));
        $channel->expects($this->any())
            ->method('getSynchronizationSettings')
            ->will($this->returnValue($this->syncSettings));
        $this->em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($channelRepository));
        $this->entity  = new Integration();
        $this->handler = new IntegrationHandler($this->request, $this->form, $this->em, $this->eventDispatcher);
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
        $this->request->setMethod($method);

        $this->form->expects($this->once()) ->method('setData')
            ->with($this->entity);
        $this->form->expects($this->once()) ->method('submit')
            ->with($this->request);

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
        $this->request->setMethod('POST');

        $this->form->expects($this->once())->method('setData')->with($this->entity);
        $this->form->expects($this->once()) ->method('submit') ->with($this->request);
        $this->form->expects($this->once()) ->method('isValid')
            ->will($this->returnValue(true));

        $this->em->expects($this->once()) ->method('persist') ->with($this->entity);
        $this->em->expects($this->once())->method('flush');

        $this->assertTrue($this->handler->process($this->entity));
    }

    /**
     * @dataProvider eventDataProvider
     *
     * @param Integration   $entity
     * @param null|User $newOwner
     * @param bool      $expectedDispatch
     */
    public function testDefaultUserOwnerSetEventDispatching($entity, $newOwner, $expectedDispatch)
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())->method('setData')->with($entity)
            ->will(
                $this->returnCallback(
                    function ($entity) use ($newOwner) {
                        $entity->setDefaultUserOwner($newOwner);
                    }
                )
            );
        $this->form->expects($this->once()) ->method('submit') ->with($this->request);
        $this->form->expects($this->once()) ->method('isValid')
            ->will($this->returnValue(true));

        $this->em->expects($this->once()) ->method('persist') ->with($entity);
        $this->em->expects($this->once())->method('flush');

        if ($expectedDispatch) {
            $this->eventDispatcher->expects($this->once())->method('dispatch')
                ->with(
                    $this->equalTo(DefaultOwnerSetEvent::NAME),
                    $this->isInstanceOf('Oro\Bundle\IntegrationBundle\Event\DefaultOwnerSetEvent')
                );
        } else {
            $this->eventDispatcher->expects($this->never())->method('dispatch');
        }

        $this->assertTrue($this->handler->process($entity));
    }

    public function testTwoWaySyncEnableEventNotDispatch()
    {
        $id = 42;
        $this->request->setMethod('POST');
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');
        //case if channel is new
        $this->handler->process($this->entity);
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $channel->expects($this->exactly(4))
            ->method('getId')
            ->will($this->returnValue($id));
        $this->form->expects($this->exactly(2)) ->method('isValid')
            ->will($this->returnValue(true));
        $settings = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Common\Object')
            ->disableOriginalConstructor()
            ->getMock();
        $settings->expects($this->once())
            ->method('offsetGetOr')
            ->will($this->returnValue(false));
        $channel->expects($this->once())
            ->method('getSynchronizationSettings')
            ->will($this->returnValue($settings));
        //case if channel is not new but reverse sync is disabled on form
        $this->handler->process($channel);
        //case if reverse sync will previously set
        $this->syncSettings->expects($this->once())
            ->method('offsetGetOr')
            ->will($this->returnValue(true));
        $this->handler->process($channel);
    }

    public function testTwoWaySyncEnableEventDispatch()
    {
        $id = 42;
        $this->request->setMethod('POST');
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $channel->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue($id));
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));
        $settings = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Common\Object')
            ->disableOriginalConstructor()
            ->getMock();
        $settings->expects($this->once())
            ->method('offsetGetOr')
            ->will($this->returnValue(true));
        $channel->expects($this->once())
            ->method('getSynchronizationSettings')
            ->will($this->returnValue($settings));
        $this->syncSettings->expects($this->once())
            ->method('offsetGetOr')
            ->will($this->returnValue(false));
        $this->handler->process($channel);
    }
    /**
     * @return array
     */
    public function eventDataProvider()
    {
        $newIntegration = new Integration();
        $newOwner   = $this->getMock('Oro\Bundle\UserBundle\Entity\User');

        $refProperty = new \ReflectionProperty('Oro\Bundle\IntegrationBundle\Entity\Channel', 'id');
        $refProperty->setAccessible(true);

        $oldIntegration = new Integration();
        $refProperty->setValue($oldIntegration, 123);

        $someOwner           = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $oldIntegrationWithOwner = clone $oldIntegration;
        $oldIntegrationWithOwner->setDefaultUserOwner($someOwner);

        return [
            'new entity, should not dispatch'              => [$newIntegration, $newOwner, false],
            'integration is not new, but owner existed before' => [$oldIntegrationWithOwner, $newOwner, false],
            'old integration without owner, should dispatch'   => [$oldIntegration, $newOwner, true]
        ];
    }
}
