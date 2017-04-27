<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\SecurityBundle\EventListener\ConsoleContextListener;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class ConsoleContextListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $userRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $organizationRepository;

    /** @var TokenStorage */
    protected $tokenStorage;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $userManager;

    /** @var ConsoleContextListener */
    protected $listener;

    protected function setUp()
    {
        $this->tokenStorage = new TokenStorage();
        $this->userManager = $this->getMockBuilder(UserManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->organizationRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                ['OroUserBundle:User', null, $this->userRepository],
                ['OroOrganizationBundle:Organization', null, $this->organizationRepository],
            ]);

        $container = TestContainerBuilder::create()
            ->add('doctrine', $doctrine)
            ->add('security.token_storage', $this->tokenStorage)
            ->add('oro_user.manager', $this->userManager)
            ->getContainer($this);

        $this->listener = new ConsoleContextListener($container);
    }

    public function testNoOptions()
    {
        $this->assertEmpty($this->tokenStorage->getToken());

        $event = $this->getEvent();
        $this->listener->onConsoleCommand($event);

        $this->assertEmpty($this->tokenStorage->getToken());
    }

    public function testSetUserIdAndOrganizationIds()
    {
        $this->assertEmpty($this->tokenStorage->getToken());

        $userId = 1;
        $user = new User();
        $user->setId($userId);

        $organizationId = 2;
        $organization = new Organization();
        $organization->setId($organizationId);
        $organization->setEnabled(true);
        $user->addOrganization($organization);

        $event = $this->getEvent();
        /** @var \PHPUnit_Framework_MockObject_MockObject $input */
        $input = $event->getInput();
        $input->expects($this->at(0))
            ->method('getParameterOption')
            ->with('--' . ConsoleContextListener::OPTION_USER)
            ->will($this->returnValue($userId));
        $input->expects($this->at(1))
            ->method('getParameterOption')
            ->with('--' . ConsoleContextListener::OPTION_ORGANIZATION)
            ->will($this->returnValue($organizationId));

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->will($this->returnValue($user));
        $this->organizationRepository->expects($this->once())
            ->method('find')
            ->with($organizationId)
            ->will($this->returnValue($organization));

        $this->listener->onConsoleCommand($event);
        /** @var ConsoleToken $token */
        $token = $this->tokenStorage->getToken();

        $this->assertNotEmpty($token);
        $this->assertInstanceOf(ConsoleToken::class, $token);
        $this->assertEquals($user, $token->getUser());
        $this->assertEquals($organization, $token->getOrganizationContext());
    }

    public function testSetUsernameAndOrganizationName()
    {
        $this->assertEmpty($this->tokenStorage->getToken());

        $username = 'test_user';
        $user = new User();
        $user->setUsername($username);

        $organizationName = 'test_organization';
        $organization = new Organization();
        $organization->setName($organizationName);
        $organization->setEnabled(true);
        $user->addOrganization($organization);

        $event = $this->getEvent();
        /** @var \PHPUnit_Framework_MockObject_MockObject $input */
        $input = $event->getInput();
        $input->expects($this->at(0))
            ->method('getParameterOption')
            ->with('--' . ConsoleContextListener::OPTION_USER)
            ->will($this->returnValue($username));
        $input->expects($this->at(1))
            ->method('getParameterOption')
            ->with('--' . ConsoleContextListener::OPTION_ORGANIZATION)
            ->will($this->returnValue($organizationName));

        $this->userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->will($this->returnValue($user));
        $this->organizationRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $organizationName])
            ->will($this->returnValue($organization));

        $this->listener->onConsoleCommand($event);
        /** @var ConsoleToken $token */
        $token = $this->tokenStorage->getToken();

        $this->assertNotEmpty($token);
        $this->assertInstanceOf(ConsoleToken::class, $token);
        $this->assertEquals($user, $token->getUser());
        $this->assertEquals($organization, $token->getOrganizationContext());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Can't find user with identifier test_user
     */
    public function testNoUserFound()
    {
        $username = 'test_user';

        $event = $this->getEvent();
        /** @var \PHPUnit_Framework_MockObject_MockObject $input */
        $input = $event->getInput();
        $input->expects($this->at(0))
            ->method('getParameterOption')
            ->with('--' . ConsoleContextListener::OPTION_USER)
            ->will($this->returnValue($username));

        $this->userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->will($this->returnValue(null));

        $this->listener->onConsoleCommand($event);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Can't find organization with identifier test_organization
     */
    public function testNoOrganizationFound()
    {
        $organizationName = 'test_organization';

        $event = $this->getEvent();
        /** @var \PHPUnit_Framework_MockObject_MockObject $input */
        $input = $event->getInput();
        $input->expects($this->at(1))
            ->method('getParameterOption')
            ->with('--' . ConsoleContextListener::OPTION_ORGANIZATION)
            ->will($this->returnValue($organizationName));

        $this->organizationRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $organizationName])
            ->will($this->returnValue(null));

        $this->listener->onConsoleCommand($event);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Organization test_organization is not enabled
     */
    public function testNotEnabledOrganization()
    {
        $organizationName = 'test_organization';
        $organization = new Organization();
        $organization->setName($organizationName);

        $event = $this->getEvent();
        /** @var \PHPUnit_Framework_MockObject_MockObject $input */
        $input = $event->getInput();
        $input->expects($this->at(1))
            ->method('getParameterOption')
            ->with('--' . ConsoleContextListener::OPTION_ORGANIZATION)
            ->will($this->returnValue($organizationName));

        $this->organizationRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $organizationName])
            ->will($this->returnValue($organization));

        $this->listener->onConsoleCommand($event);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage User test_user is not in organization test_organization
     */
    public function testUserNotInOrganization()
    {
        $username = 'test_user';
        $user = new User();
        $user->setUsername($username);

        $organizationName = 'test_organization';
        $organization = new Organization();
        $organization->setName($organizationName);
        $organization->setEnabled(true);

        $event = $this->getEvent();
        /** @var \PHPUnit_Framework_MockObject_MockObject $input */
        $input = $event->getInput();
        $input->expects($this->at(0))
            ->method('getParameterOption')
            ->with('--' . ConsoleContextListener::OPTION_USER)
            ->will($this->returnValue($username));
        $input->expects($this->at(1))
            ->method('getParameterOption')
            ->with('--' . ConsoleContextListener::OPTION_ORGANIZATION)
            ->will($this->returnValue($organizationName));

        $this->userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->will($this->returnValue($user));
        $this->organizationRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => $organizationName])
            ->will($this->returnValue($organization));

        $this->listener->onConsoleCommand($event);
    }

    /**
     * @return ConsoleCommandEvent
     */
    protected function getEvent()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|InputDefinition $definition */
        $definition = $this->getMockBuilder(InputDefinition::class)
            ->disableOriginalConstructor()
            ->setMethods(['addOption', 'getParameterOption'])
            ->getMock();
        $definition->expects($this->at(0))
            ->method('addOption')
            ->with($this->isInstanceOf(InputOption::class))
            ->will(
                $this->returnCallback(
                    function (InputOption $option) {
                        $this->assertEquals(ConsoleContextListener::OPTION_USER, $option->getName());
                    }
                )
            );
        $definition->expects($this->at(1))
            ->method('addOption')
            ->with($this->isInstanceOf('Symfony\Component\Console\Input\InputOption'))
            ->will(
                $this->returnCallback(
                    function (InputOption $option) {
                        $this->assertEquals(ConsoleContextListener::OPTION_ORGANIZATION, $option->getName());
                    }
                )
            );

        /** @var \PHPUnit_Framework_MockObject_MockObject|Application $application */
        $application = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->setMethods(['getHelperSet'])
            ->getMock();
        $application->setDefinition($definition);
        $application->expects($this->any())
            ->method('getHelperSet')
            ->will($this->returnValue(new HelperSet()));

        /** @var \PHPUnit_Framework_MockObject_MockObject|Command $command */
        $command = $this->getMockBuilder(Command::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $command->setApplication($application);
        $command->setDefinition($definition);

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        return new ConsoleCommandEvent($command, $input, $output);
    }
}
