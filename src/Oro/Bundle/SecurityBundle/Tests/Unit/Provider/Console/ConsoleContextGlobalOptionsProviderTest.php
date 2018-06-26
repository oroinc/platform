<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Provider\Console;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Bundle\SecurityBundle\Provider\Console\ConsoleContextGlobalOptionsProvider;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Role\RoleInterface;

class ConsoleContextGlobalOptionsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $container;

    /**
     * @var ConsoleContextGlobalOptionsProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->provider = new ConsoleContextGlobalOptionsProvider($this->container);
    }

    public function testAddGlobalOptions()
    {
        $inputDefinition = new InputDefinition();
        $application = $this->createMock(Application::class);
        $application->expects($this->any())
            ->method('getDefinition')
            ->willReturn($inputDefinition);
        $application->expects($this->once())
            ->method('getHelperSet')
            ->willReturn(new HelperSet());

        $commandDefinition = new InputDefinition();
        $command = new Command('test');
        $command->setApplication($application);
        $command->setDefinition($commandDefinition);

        $this->provider->addGlobalOptions($command);
        $this->assertEquals(
            [
                ConsoleContextGlobalOptionsProvider::OPTION_USER,
                ConsoleContextGlobalOptionsProvider::OPTION_ORGANIZATION,
            ],
            array_keys($command->getApplication()->getDefinition()->getOptions())
        );
        $this->assertEquals(
            [
                ConsoleContextGlobalOptionsProvider::OPTION_USER,
                ConsoleContextGlobalOptionsProvider::OPTION_ORGANIZATION,
            ],
            array_keys($command->getDefinition()->getOptions())
        );
    }

    public function testResolveGlobalOptionsWhenNoUserAndOrganization()
    {
        /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject $input */
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))
            ->method('getParameterOption')
            ->withConsecutive(
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_USER],
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_ORGANIZATION]
            )
            ->willReturn(null, null);
        $this->container->expects($this->never())
            ->method('get');
        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenNoUser()
    {
        $organizationId = 777;
        /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject $input */
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))
            ->method('getParameterOption')
            ->withConsecutive(
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_USER],
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_ORGANIZATION]
            )
            ->willReturn(null, $organizationId);

        $tokenStorage = new TokenStorage();
        $this->container->expects($this->once())
            ->method('get')
            ->with('security.token_storage')
            ->willReturn($tokenStorage);
        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenUserIsNotFound()
    {
        $userId = 777;
        /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject $input */
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))
            ->method('getParameterOption')
            ->withConsecutive(
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_USER],
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_ORGANIZATION]
            )
            ->willReturn($userId, null);

        $registry = $this->createMock(RegistryInterface::class);
        $tokenStorage = new TokenStorage();
        $this->container->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['security.token_storage'],
                ['doctrine']
            )
            ->willReturnOnConsecutiveCalls($tokenStorage, $registry);

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn(null);
        $registry->expects($this->once())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->willReturn($repository);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Can\'t find user with identifier %s', $userId));
        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenUserIsIntAndNoOrganization()
    {
        $userId = 777;
        /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject $input */
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))
            ->method('getParameterOption')
            ->withConsecutive(
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_USER],
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_ORGANIZATION]
            )
            ->willReturn($userId, null);

        $registry = $this->createMock(RegistryInterface::class);
        $tokenStorage = new TokenStorage();
        $this->container->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['security.token_storage'],
                ['doctrine']
            )
            ->willReturnOnConsecutiveCalls($tokenStorage, $registry);

        /** @var RoleInterface $role */
        $role = $this->createMock(RoleInterface::class);
        $user = new User();
        $user->addRole($role);
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);
        $registry->expects($this->once())
            ->method('getRepository')
            ->with('OroUserBundle:User')
            ->willReturn($repository);

        $expectedToken = new ConsoleToken([$role]);
        $expectedToken->setUser($user);

        $this->provider->resolveGlobalOptions($input);
        $this->assertEquals($expectedToken, $tokenStorage->getToken());
    }

    public function testResolveGlobalOptionsWhenUserIsStringAndNoOrganization()
    {
        $username = 'username';
        /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject $input */
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))
            ->method('getParameterOption')
            ->withConsecutive(
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_USER],
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_ORGANIZATION]
            )
            ->willReturn($username, null);

        $userManager = $this->createMock(UserManager::class);
        $tokenStorage = new TokenStorage();
        $this->container->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['security.token_storage'],
                ['oro_user.manager']
            )
            ->willReturnOnConsecutiveCalls($tokenStorage, $userManager);

        /** @var RoleInterface $role */
        $role = $this->createMock(RoleInterface::class);
        $user = new User();
        $user->addRole($role);
        $userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->willReturn($user);

        $expectedToken = new ConsoleToken([$role]);
        $expectedToken->setUser($user);

        $this->provider->resolveGlobalOptions($input);
        $this->assertEquals($expectedToken, $tokenStorage->getToken());
    }

    public function testResolveGlobalOptionsWhenUserIsStringAndOrganizationIsNotFound()
    {
        $username = 'username';
        $organizationId = 777;
        /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject $input */
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))
            ->method('getParameterOption')
            ->withConsecutive(
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_USER],
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_ORGANIZATION]
            )
            ->willReturn($username, $organizationId);

        $userManager = $this->createMock(UserManager::class);
        $tokenStorage = new TokenStorage();

        $registry = $this->createMock(RegistryInterface::class);
        $this->container->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['security.token_storage'],
                ['oro_user.manager'],
                ['doctrine']
            )
            ->willReturnOnConsecutiveCalls($tokenStorage, $userManager, $registry);

        $user = new User();
        $userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->willReturn($user);

        $organizationRepository = $this->getOrganizationRepository($registry);
        $organizationRepository->expects($this->once())
            ->method('find')
            ->with($organizationId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Can\'t find organization with identifier %s', $organizationId));
        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenUserIsStringAndOrganizationIsNotEnabled()
    {
        $username = 'username';
        $organizationId = 555;
        /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject $input */
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))
            ->method('getParameterOption')
            ->withConsecutive(
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_USER],
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_ORGANIZATION]
            )
            ->willReturn($username, $organizationId);

        $userManager = $this->createMock(UserManager::class);
        $tokenStorage = new TokenStorage();

        $registry = $this->createMock(RegistryInterface::class);
        $this->container->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['security.token_storage'],
                ['oro_user.manager'],
                ['doctrine']
            )
            ->willReturnOnConsecutiveCalls($tokenStorage, $userManager, $registry);

        $user = new User();
        $userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->willReturn($user);

        $organization = new Organization();
        $organization->setEnabled(false);
        $organization->setName('testorg');
        $organizationRepository = $this->getOrganizationRepository($registry);
        $organizationRepository->expects($this->once())
            ->method('find')
            ->with($organizationId)
            ->willReturn($organization);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Organization %s is not enabled', $organization->getName()));
        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenUserNotFromCurrentOrganization()
    {
        $username = 'username';
        $organizationId = 555;
        /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject $input */
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))
            ->method('getParameterOption')
            ->withConsecutive(
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_USER],
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_ORGANIZATION]
            )
            ->willReturn($username, $organizationId);

        $userManager = $this->createMock(UserManager::class);
        $tokenStorage = new TokenStorage();

        $registry = $this->createMock(RegistryInterface::class);
        $this->container->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['security.token_storage'],
                ['oro_user.manager'],
                ['doctrine']
            )
            ->willReturnOnConsecutiveCalls($tokenStorage, $userManager, $registry);

        $organization = new Organization();
        $organization->setEnabled(true);
        $organization->setName('testneworg');
        $organizationRepository = $this->getOrganizationRepository($registry);
        $organizationRepository->expects($this->once())
            ->method('find')
            ->with($organizationId)
            ->willReturn($organization);

        /** @var RoleInterface $role */
        $user = new User();
        $user->setUsername('testnewusername');
        $userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->willReturn($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'User %s is not in organization %s',
            $user->getUsername(),
            $organization->getName()
        ));
        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptions()
    {
        $username = 'username';
        $organizationId = 555;
        /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject $input */
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->exactly(2))
            ->method('getParameterOption')
            ->withConsecutive(
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_USER],
                ['--' . ConsoleContextGlobalOptionsProvider::OPTION_ORGANIZATION]
            )
            ->willReturn($username, $organizationId);

        $userManager = $this->createMock(UserManager::class);
        $tokenStorage = new TokenStorage();

        $registry = $this->createMock(RegistryInterface::class);
        $this->container->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                ['security.token_storage'],
                ['oro_user.manager'],
                ['doctrine']
            )
            ->willReturnOnConsecutiveCalls($tokenStorage, $userManager, $registry);

        $organization = new Organization();
        $organization->setEnabled(true);
        $organization->setName('testneworg');
        $organizationRepository = $this->getOrganizationRepository($registry);
        $organizationRepository->expects($this->once())
            ->method('find')
            ->with($organizationId)
            ->willReturn($organization);

        /** @var RoleInterface $role */
        $role = $this->createMock(RoleInterface::class);
        $user = new User();
        $user->setUsername('testnewusername');
        $user->addRole($role);
        $user->addOrganization($organization);
        $userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->willReturn($user);

        $expectedToken = new ConsoleToken([$role]);
        $expectedToken->setUser($user);
        $expectedToken->setOrganizationContext($organization);

        $this->provider->resolveGlobalOptions($input);
        $this->assertEquals($expectedToken, $tokenStorage->getToken());
    }

    /**
     * @param RegistryInterface|\PHPUnit\Framework\MockObject\MockObject $registry
     * @return OrganizationRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getOrganizationRepository(RegistryInterface $registry)
    {
        $repository = $this->createMock(OrganizationRepository::class);
        $registry->expects($this->once())
            ->method('getRepository')
            ->with('OroOrganizationBundle:Organization')
            ->willReturn($repository);

        return $repository;
    }
}
