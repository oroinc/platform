<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Http\Firewall;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Exception\OrganizationAccessDeniedException;
use Oro\Bundle\SecurityBundle\Http\Firewall\ContextListener;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;

class ContextListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject $tokenStorage;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private ContextListener $listener;

    protected function setUp(): void
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
     */
    public function testOnKernelRequestUnsupportedTokenInstance(TokenInterface $token): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::never())
            ->method(self::anything());
        $this->doctrine->expects(self::never())
            ->method(self::anything());

        $this->listener->onKernelRequest($event);
    }

    public function unsupportedTokenDataProvider(): array
    {
        return [
            'invalid interface'       => [$this->createMock(TokenInterface::class)],
            'no organization context' => [$this->createMock(OrganizationAwareTokenInterface::class)]
        ];
    }

    public function testOnKernelRequestCannotSetOrganizationForNotSupportedUserException(): void
    {
        $user = null;
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 1]);
        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $token->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);
        $token->expects(self::any())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn($token);

        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::never())
            ->method(self::anything());

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Organization::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Organization::class, $organization->getId())
            ->willReturn(null);

        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('set')
            ->with(Security::AUTHENTICATION_ERROR, self::isInstanceOf(OrganizationAccessDeniedException::class));

        $request = $this->createMock(Request::class);
        $request->expects(self::any())
            ->method('hasSession')
            ->willReturn(true);
        $request->expects(self::any())
            ->method('getSession')
            ->willReturn($session);
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::any())
            ->method('getRequest')
            ->willReturn($request);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Could not find organization by id 1');

        $this->expectException(OrganizationAccessDeniedException::class);
        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestCannotSetOrganizationForNotSupportedUser(): void
    {
        $user = null;
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 1]);
        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $token->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);
        $token->expects(self::any())
            ->method('getUser')
            ->willReturn($user);
        $token->expects(self::once())
            ->method('setOrganization')
            ->with($organization);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::never())
            ->method(self::anything());

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Organization::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Organization::class, $organization->getId())
            ->willReturn($organization);

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestOrganizationAccessAllowed(): void
    {
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 1]);

        $user = $this->createMock(AbstractUser::class);
        $user->expects(self::once())
            ->method('isBelongToOrganization')
            ->with(self::identicalTo($organization), self::isTrue())
            ->willReturn(true);

        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $token->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);
        $token->expects(self::any())
            ->method('getUser')
            ->willReturn($user);
        $token->expects(self::once())
            ->method('setOrganization')
            ->with($organization);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::never())
            ->method(self::anything());

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Organization::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Organization::class, $organization->getId())
            ->willReturn($organization);

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestOrganizationAccessDenied(): void
    {
        /** @var Organization $organization */
        $organization = $this->getEntity(Organization::class, ['id' => 1, 'name' => 'from context']);

        $user = $this->createMock(AbstractUser::class);
        $user->expects(self::once())
            ->method('isBelongToOrganization')
            ->with(self::identicalTo($organization), self::isTrue())
            ->willReturn(false);

        $token = $this->createMock(OrganizationAwareTokenInterface::class);
        $token->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);
        $token->expects(self::any())
            ->method('getUser')
            ->willReturn($user);
        $token->expects(self::once())
            ->method('setOrganization');

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn($token);
        $this->tokenStorage->expects(self::once())
            ->method('setToken')
            ->with(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Organization::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with(Organization::class, $organization->getId())
            ->willReturn($organization);

        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('set')
            ->with(Security::AUTHENTICATION_ERROR, self::isInstanceOf(OrganizationAccessDeniedException::class));

        $request = $this->createMock(Request::class);
        $request->expects(self::any())
            ->method('hasSession')
            ->willReturn(true);
        $request->expects(self::any())
            ->method('getSession')
            ->willReturn($session);
        $event = $this->createMock(RequestEvent::class);
        $event->expects(self::any())
            ->method('getRequest')
            ->willReturn($request);

        $this->expectException(OrganizationAccessDeniedException::class);

        $this->listener->onKernelRequest($event);
    }
}
