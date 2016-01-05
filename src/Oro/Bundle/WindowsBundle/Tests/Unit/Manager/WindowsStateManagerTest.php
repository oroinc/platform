<?php

namespace Oro\Bundle\WindowsBundle\Tests\Manager;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WindowsBundle\Entity\WindowsState;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManager;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateRequestManager;

class WindowsStateManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface */
    protected $tokenStorage;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|WindowsStateRequestManager */
    protected $requestStateManager;

    /** @var WindowsStateManager */
    protected $manager;

    protected function setUp()
    {
        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStateManager = $this
            ->getMockBuilder('Oro\Bundle\WindowsBundle\Manager\WindowsStateRequestManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new WindowsStateManager(
            $this->tokenStorage,
            $this->doctrineHelper,
            $this->requestStateManager,
            'Oro\Bundle\WindowsBundle\Entity\WindowsState',
            'Oro\Bundle\UserBundle\Entity\User'
        );
    }

    public function testCreateWindowsState()
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())->method('getUser')->willReturn(new User());
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $em = $this->getMock('Doctrine\ORM\EntityManagerInterface');

        $state = new WindowsState();
        $this->doctrineHelper->expects($this->once())->method('createEntityInstance')->willReturn($state);
        $this->doctrineHelper->expects($this->once())->method('getEntityManagerForClass')->willReturn($em);

        $em->expects($this->once())->method('persist')->with($state);
        $em->expects($this->once())->method('flush')->with($state);

        $this->requestStateManager->expects($this->once())->method('getData')->willReturn([]);
        $this->manager->createWindowsState();
    }

    public function testUpdateWindowState()
    {
        $windowId = 42;
        $user = new User();
        $data = ['url' => '/test'];

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())->method('getUser')->willReturn($user);
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $repo = $this->getMockBuilder('Oro\Bundle\WindowsBundle\Entity\Repository\WindowsStateRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);
        $this->requestStateManager->expects($this->once())->method('getData')->willReturn($data);

        $repo->expects($this->once())->method('update')->with($user, $windowId, $data);

        $this->manager->updateWindowsState($windowId);
    }

    public function testDeleteWindowsState()
    {
        $user = new User();
        $windowId = 42;

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())->method('getUser')->willReturn($user);
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $repo = $this->getMockBuilder('Oro\Bundle\WindowsBundle\Entity\Repository\WindowsStateRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);

        $repo->expects($this->once())->method('delete')->with($user, $windowId);

        $this->requestStateManager->expects($this->never())->method($this->anything());
        $this->manager->deleteWindowsState($windowId);
    }

    public function testGetWindowsStates()
    {
        $user = new User();

        $windowStateFoo = $this->createWindowState(['cleanUrl' => 'foo']);
        $windowStateBar = $this->createWindowState(['cleanUrl' => 'foo']);

        $windowStates = [$windowStateFoo, $windowStateBar];

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())->method('getUser')->willReturn($user);
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $repo = $this->getMockBuilder('Oro\Bundle\WindowsBundle\Entity\Repository\WindowsStateRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);

        $repo->expects($this->once())->method('findBy')->with(['user' => $user])->willReturn($windowStates);

        $this->requestStateManager->expects($this->never())->method($this->anything());
        $this->assertSame($windowStates, $this->manager->getWindowsStates());
    }

    public function testGetWindowsState()
    {
        $user = new User();
        $windowStateId = 42;

        $windowState = $this->createWindowState(['cleanUrl' => 'foo']);

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())->method('getUser')->willReturn($user);
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $repo = $this->getMockBuilder('Oro\Bundle\WindowsBundle\Entity\Repository\WindowsStateRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);

        $repo->expects($this->once())->method('findBy')->with(['user' => $user, 'id' => $windowStateId])
            ->willReturn($windowState);

        $this->requestStateManager->expects($this->never())->method($this->anything());
        $this->assertSame($windowState, $this->manager->getWindowsState($windowStateId));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Wrong $windowId type
     */
    public function testFilterId()
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())->method('getUser')->willReturn(new User());
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $repo = $this->getMockBuilder('Oro\Bundle\WindowsBundle\Entity\Repository\WindowsStateRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);

        $this->manager->getWindowsState('bbb');
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testUserEmptyToken()
    {
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn(null);

        $repo = $this->getMockBuilder('Oro\Bundle\WindowsBundle\Entity\Repository\WindowsStateRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);

        $this->manager->getWindowsState(42);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testUserEmptyUser()
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())->method('getUser')->willReturn(null);
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $repo = $this->getMockBuilder('Oro\Bundle\WindowsBundle\Entity\Repository\WindowsStateRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);

        $this->manager->getWindowsState(42);
    }

    /**
     * @param array $data
     * @return WindowsState
     */
    protected function createWindowState(array $data = [])
    {
        $state = new WindowsState();
        $state->setData($data);

        return $state;
    }

    public function testIsApplicableWithoutUser()
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())->method('getUser')->willReturn(null);
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $this->assertFalse($this->manager->isApplicable());
    }

    public function testIsApplicable()
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())->method('getUser')->willReturn(new User());
        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $this->assertTrue($this->manager->isApplicable());
    }
}
