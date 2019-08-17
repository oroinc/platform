<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Http\Firewall;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Exception\OrganizationAccessDeniedException;
use Oro\Bundle\SecurityBundle\Http\Firewall\ContextListener;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;

class ContextListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ContextListener */
    private $listener;

    protected function setUp()
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->listener = new ContextListener(
            $this->tokenStorage,
            $this->doctrine,
            $this->logger
        );
    }

    /**
     * @dataProvider unsupportedTokenDataProvider
     *
     * @param mixed $token
     */
    public function testOnKernelRequestUnsupportedTokenInstance($token)
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        /** @var GetResponseEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(GetResponseEvent::class);
        $event->expects($this->never())
            ->method($this->anything());
        $this->doctrine->expects($this->never())
            ->method($this->anything());

        $this->listener->onKernelRequest($event);
    }

    /**
     * @return array
     */
    public function unsupportedTokenDataProvider()
    {
        return [
            'invalid interface'       => [$this->createMock(TokenInterface::class)],
            'no organization context' => [$this->createMock(OrganizationContextTokenInterface::class)]
        ];
    }

    public function testOnKernelRequestCannotSetOrganizationForNotSupportedUserException()
    {
        $user = null;
        /** @var Organization $organizationContext */
        $organizationContext = $this->getEntity(Organization::class, ['id' => 1]);
        $token = $this->createMock(OrganizationContextTokenInterface::class);
        $token->expects($this->any())
            ->method('getOrganizationContext')
            ->willReturn($organizationContext);
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->atLeastOnce())
            ->method('getToken')
            ->willReturn($token);
        /** @var GetResponseEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(GetResponseEvent::class);
        $event->expects($this->never())
            ->method($this->anything());

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Organization::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('find')
            ->with(Organization::class, $organizationContext->getId())
            ->willReturn(null);

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('set')
            ->with(Security::AUTHENTICATION_ERROR, $this->isInstanceOf(OrganizationAccessDeniedException::class));

        $request = $this->createMock(Request::class);
        $request->expects($this->any())
            ->method('getSession')
            ->willReturn($session);
        $event = $this->createMock(GetResponseEvent::class);
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Could not find organization by id 1');

        $this->expectException(OrganizationAccessDeniedException::class);
        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestCannotSetOrganizationForNotSupportedUser()
    {
        $user = null;
        /** @var Organization $organizationContext */
        $organizationContext = $this->getEntity(Organization::class, ['id' => 1]);
        $token = $this->createMock(OrganizationContextTokenInterface::class);
        $token->expects($this->any())
            ->method('getOrganizationContext')
            ->willReturn($organizationContext);
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);
        $token->expects($this->once())
            ->method('setOrganizationContext')
            ->with($organizationContext);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        /** @var GetResponseEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(GetResponseEvent::class);
        $event->expects($this->never())
            ->method($this->anything());

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Organization::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('find')
            ->with(Organization::class, $organizationContext->getId())
            ->willReturn($organizationContext);

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestOrganizationAccessAllowed()
    {
        /** @var Organization $organizationContext */
        $organizationContext = $this->getEntity(Organization::class, ['id' => 1]);

        $user = $this->createMock(AbstractUser::class);
        $user->expects($this->once())
            ->method('isBelongToOrganization')
            ->with($this->identicalTo($organizationContext), $this->isTrue())
            ->willReturn(true);

        $token = $this->createMock(OrganizationContextTokenInterface::class);
        $token->expects($this->any())
            ->method('getOrganizationContext')
            ->willReturn($organizationContext);
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);
        $token->expects($this->once())
            ->method('setOrganizationContext')
            ->with($organizationContext);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        /** @var GetResponseEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(GetResponseEvent::class);
        $event->expects($this->never())
            ->method($this->anything());

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Organization::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('find')
            ->with(Organization::class, $organizationContext->getId())
            ->willReturn($organizationContext);

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestOrganizationAccessDenied()
    {
        /** @var Organization $organizationContext */
        $organizationContext = $this->getEntity(Organization::class, ['id' => 1, 'name' => 'from context']);

        $user = $this->createMock(AbstractUser::class);
        $user->expects($this->once())
            ->method('isBelongToOrganization')
            ->with($this->identicalTo($organizationContext), $this->isTrue())
            ->willReturn(false);

        $token = $this->createMock(OrganizationContextTokenInterface::class);
        $token->expects($this->any())
            ->method('getOrganizationContext')
            ->willReturn($organizationContext);
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);
        $token->expects($this->once())
            ->method('setOrganizationContext');

        $this->tokenStorage->expects($this->atLeastOnce())
            ->method('getToken')
            ->willReturn($token);
        $this->tokenStorage->expects($this->once())
            ->method('setToken')
            ->with(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(Organization::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('find')
            ->with(Organization::class, $organizationContext->getId())
            ->willReturn($organizationContext);

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('set')
            ->with(Security::AUTHENTICATION_ERROR, $this->isInstanceOf(OrganizationAccessDeniedException::class));

        $request = $this->createMock(Request::class);
        $request->expects($this->any())
            ->method('getSession')
            ->willReturn($session);
        /** @var GetResponseEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(GetResponseEvent::class);
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->expectException(OrganizationAccessDeniedException::class);

        $this->listener->onKernelRequest($event);
    }
}
