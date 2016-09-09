<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Oro\Bundle\EntityBundle\Event\OroEventManager;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\NavigationBundle\Event\ResponseHistoryListener;
use Oro\Bundle\NavigationBundle\Provider\TitleService;
use Oro\Bundle\UserBundle\Entity\User;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ResponseHistoryListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityContext;

    /**
     * @var ResponseHistoryListener
     */
    protected $listener;

    /**
     * @var NavigationHistoryItem|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $item;

    /**
     * @var \Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var TitleService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $titleService;

    /**
     * @var string
     */
    protected $serializedTitle;

    protected function setUp()
    {
        $this->factory         = $this->getMock('Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory');
        $this->securityContext = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');

        $user = new User();
        $user->setEmail('some@email.com');

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())->method('getUser')
            ->will($this->returnValue($user));

        $this->securityContext->expects($this->any())->method('getToken')
            ->will($this->returnValue($token));

        $this->item = $this->getMock('Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem');

        $this->serializedTitle = json_encode(array('titleTemplate' => 'Test title template'));
    }

    /**
     * @dataProvider onResponseProvider
     *
     * @param $eventManager
     */
    public function testOnResponse($eventManager)
    {
        $response = $this->getResponse();

        $repository = $this->getDefaultRepositoryMock($this->item);
        $em         = $this->getEntityManager($repository, $eventManager);

        $listener = $this->getListener($this->factory, $this->securityContext, $em);
        $listener->onResponse($this->getEventMock($this->getRequest(), $response));
    }

    public function onResponseProvider()
    {
        return array(
            'with enabling/disabling listeners'    => array('Oro\Bundle\EntityBundle\Event\OroEventManager'),
            'without enabling/disabling listeners' => array('Doctrine\Common\EventManager')
        );
    }

    public function testTitle()
    {
        $this->item->expects($this->once())
            ->method('setTitle')
            ->with($this->equalTo($this->serializedTitle));

        $response   = $this->getResponse();
        $repository = $this->getDefaultRepositoryMock($this->item);
        $em         = $this->getEntityManager($repository);

        $listener = $this->getListener($this->factory, $this->securityContext, $em);
        $listener->onResponse($this->getEventMock($this->getRequest(), $response));
    }

    public function testNewItem()
    {
        $user = new User();
        $user->setEmail('some@email.com');

        $this->factory->expects($this->once())
            ->method('createItem')
            ->will($this->returnValue($this->item));

        $repository = $this->getDefaultRepositoryMock(null);
        $em         = $this->getEntityManager($repository);

        $listener = $this->getListener($this->factory, $this->securityContext, $em);
        $response = $this->getResponse();

        $listener->onResponse($this->getEventMock($this->getRequest(), $response));
    }

    public function testNotMasterRequest()
    {
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\FilterResponseEvent')
            ->disableOriginalConstructor()->getMock();

        $event->expects($this->never())
            ->method('getRequest');
        $event->expects($this->never())
            ->method('getResponse');
        $event->expects($this->once())
            ->method('getRequestType')
            ->will($this->returnValue(HttpKernelInterface::SUB_REQUEST));

        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->never())->method('getManagerForClass');

        $titleService = $this->getMock('Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface');

        $listener = new ResponseHistoryListener($this->factory, $this->securityContext, $registry, $titleService);
        $listener->onResponse($event);
    }

    public function testLongHistoryUrlCut()
    {
        $response   = $this->getResponse();
        $repository = $this->getDefaultRepositoryMock(null);
        $request = $this->getRequest();
        $request->expects($this->once())->method('getRequestUri')->will($this->returnValue(str_repeat('a', 200)));
        $em = $this->getEntityManager($repository);

        $this->factory->expects($this->once())
            ->method('createItem')
            ->with(
                'history',
                $this->callback(
                    function ($params) {
                        $this->assertEquals(100, strlen($params['url']));

                        return true;
                    }
                )
            )
            ->will($this->returnValue($this->item));

        $listener = $this->getListener($this->factory, $this->securityContext, $em);
        $listener->onResponse($this->getEventMock($request, $response));
    }

    /**
     * Get the mock of the GetResponseEvent and FilterResponseEvent.
     *
     * @param \Symfony\Component\HttpFoundation\Request       $request
     * @param null|\Symfony\Component\HttpFoundation\Response $response
     * @param string                                          $type
     *
     * @return mixed
     */
    private function getEventMock($request, $response, $type = 'Symfony\Component\HttpKernel\Event\FilterResponseEvent')
    {
        $event = $this->getMockBuilder($type)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $event->expects($this->any())
            ->method('getRequestType')
            ->will($this->returnValue(HttpKernelInterface::MASTER_REQUEST));

        $event->expects($this->any())
            ->method('getResponse')
            ->will($this->returnValue($response));

        return $event;
    }

    /**
     * Creates request mock object
     *
     * @return Request
     */
    private function getRequest()
    {
        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');

        $this->request->expects($this->once())
            ->method('getRequestFormat')
            ->will($this->returnValue('html'));

        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'));

        $this->request->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    array(
                        array('_route', 'test_route'),
                        array('_route_params', array()),
                        array('id', 1),
                    )
                )
            );

        return $this->request;
    }

    /**
     * Creates response object mock
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getResponse()
    {
        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');

        $response->expects($this->once())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        return $response;
    }

    public function getTitleService()
    {

        $this->titleService = $this->getMock('Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface');
        $this->titleService->expects($this->once())
            ->method('getSerialized')
            ->will($this->returnValue($this->serializedTitle));

        return $this->titleService;
    }

    /**
     * @param  \Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory   $factory
     * @param  \Symfony\Component\Security\Core\SecurityContextInterface $securityContext
     * @param  \Doctrine\ORM\EntityManager                               $entityManager
     *
     * @return ResponseHistoryListener
     */
    private function getListener($factory, $securityContext, $entityManager)
    {
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem')
            ->will($this->returnValue($entityManager));

        $listener = new ResponseHistoryListener($factory, $securityContext, $registry, $this->getTitleService());
        $listener->setHistoryItemEntityFQCN('Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem');
        $listener->setUserEntityFQCN('Oro\Bundle\UserBundle\Entity\User');
        $listener->setNavigationHistoryItemType('history');

        return $listener;
    }

    /**
     * Returns EntityManager
     *
     * @param  \Oro\Bundle\NavigationBundle\Entity\Repository\HistoryItemRepository $repositoryMock
     * @param  string                                                               $eventManager
     *
     * @return \Doctrine\ORM\EntityManager                                          $entityManager
     */
    private function getEntityManager($repositoryMock, $eventManager = 'Oro\Bundle\EntityBundle\Event\OroEventManager')
    {
        $eventManager = $this->getMockBuilder($eventManager)
            ->setMethods(['disableListeners', 'clearDisabledListeners'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getEventManager')
            ->will($this->returnValue($eventManager));
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem'))
            ->will($this->returnValue($repositoryMock));

        $meta = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $meta->expects($this->once())->method('getFieldMapping')->with($this->equalTo('url'))
            ->will($this->returnValue(array('length' => 100)));
        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->with($this->equalTo('Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem'))
            ->will($this->returnValue($meta));

        $shouldBeDisabled = $eventManager instanceof OroEventManager;
        if ($shouldBeDisabled) {
            $eventManager->expects($this->once())
                ->method('disableListeners')
                ->with('^Oro');
        } else {
            $eventManager->expects($this->never())
                ->method('disableListeners');
        }

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->item);
        $this->em->expects($this->once())
            ->method('flush')
            ->with($this->item);
        $eventManager->expects($shouldBeDisabled ? $this->once() : $this->never())
            ->method('clearDisabledListeners');

        return $this->em;
    }

    /**
     * Prepare repository mock
     *
     * @param  mixed $returnValue
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getDefaultRepositoryMock($returnValue)
    {
        $repository = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Entity\Repository\HistoryItemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('findOneBy')
            ->will($this->returnValue($returnValue));

        return $repository;
    }
}
