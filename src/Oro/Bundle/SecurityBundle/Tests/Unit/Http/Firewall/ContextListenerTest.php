<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Http\Firewall;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NoResultException;
use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\Exception\OrganizationAccessDeniedException;
use Oro\Bundle\SecurityBundle\Http\Firewall\ContextListener;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;

class ContextListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var OrganizationManager
     */
    private $organizationManager;

    /**
     * @var ContextListener
     */
    private $listener;

    protected function setUp()
    {
        /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->organizationManager = $this->createMock(OrganizationManager::class);

        $container->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['security.token_storage', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->tokenStorage],
                [
                    'oro_organization.organization_manager',
                    ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                    $this->organizationManager
                ],
            ]);

        $this->listener = new ContextListener($container);
    }

    /**
     * @dataProvider unsupportedTokenDataProvider
     * @param mixed $token
     */
    public function testOnKernelRequestUnsupportedTokenInstance($token)
    {
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $event = $this->createMock(GetResponseEvent::class);
        $event->expects($this->never())
            ->method($this->anything());
        $this->organizationManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onKernelRequest($event);
    }

    /**
     * @return array
     */
    public function unsupportedTokenDataProvider()
    {
        return [
            'invalid interface' => [$this->createMock(TokenInterface::class)],
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
        $token->expects($this->once())
            ->method('setAuthenticated')
            ->with(false);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $event = $this->createMock(GetResponseEvent::class);
        $event->expects($this->never())
            ->method($this->anything());

        $this->organizationManager->expects($this->once())
            ->method('getOrganizationById')
            ->willThrowException(new NoResultException());

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
        $event = $this->createMock(GetResponseEvent::class);
        $event->expects($this->never())
            ->method($this->anything());

        $this->organizationManager->expects($this->once())
            ->method('getOrganizationById')
            ->willReturn($organizationContext);

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestOrganizationAccessAllowed()
    {
        /** @var Organization $organizationContext */
        $organizationContext = $this->getEntity(Organization::class, ['id' => 1]);

        $user = $this->createMock(AbstractUser::class);
        $user->expects($this->once())
            ->method('getOrganizations')
            ->willReturn(new ArrayCollection([$organizationContext]));

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
        $event = $this->createMock(GetResponseEvent::class);
        $event->expects($this->never())
            ->method($this->anything());

        $this->listener->onKernelRequest($event);
    }

    public function testOnKernelRequestOrganizationAccessDenied()
    {
        /** @var Organization $organizationContext */
        $organizationContext = $this->getEntity(Organization::class, ['id' => 1, 'name' => 'from context']);

        /** @var Organization $knownOrganization */
        $knownOrganization = $this->getEntity(Organization::class, ['id' => 2, 'name' => 'known']);

        $user = $this->createMock(AbstractUser::class);
        $user->expects($this->once())
            ->method('getOrganizations')
            ->willReturn(new ArrayCollection([$knownOrganization]));

        $token = $this->createMock(OrganizationContextTokenInterface::class);
        $token->expects($this->any())
            ->method('getOrganizationContext')
            ->willReturn($organizationContext);
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn($user);
        $token->expects($this->never())
            ->method('setOrganizationContext');

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->tokenStorage->expects($this->once())
            ->method('setToken')
            ->with(null);

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

        $this->expectException(OrganizationAccessDeniedException::class);

        $this->listener->onKernelRequest($event);
    }
}
