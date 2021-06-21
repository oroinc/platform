<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\UserBundle\Command\ImpersonateUserCommand;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\Testing\Command\CommandTestingTrait;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Console\Tester\CommandTester;

class ImpersonateUserCommandTest extends \PHPUnit\Framework\TestCase
{
    use CommandTestingTrait;

    private const USERNAME = 'someuser';

    /** @var ImpersonateUserCommand */
    private $command;

    /** @var UserManager */
    private $userManager;

    /** @var ManagerRegistry */
    private $managerRegistry;

    public function testSuccessfulExecuteReturnsZeroAndSuggestsURL()
    {
        $this->createMocks();
        $commandTester = $this->executeCommand();

        $this->assertReturnsSuccessAndSuggestsUrl($commandTester);
    }

    public function testSuccessfulExecuteDisplaysWarningForDisabledUser()
    {
        $this->createMocks(false);

        $commandTester = $this->executeCommand();

        $this->assertReturnsSuccessAndSuggestsUrl($commandTester);
        $this->assertProducedWarning(
            $commandTester,
            'User account is disabled. You will not be able to login as this user.'
        );
    }

    public function testSuccessfulExecuteDisplaysWarningForNonActiveAuthStatus()
    {
        $this->createMocks(null, UserManager::STATUS_EXPIRED);

        $commandTester = $this->executeCommand();

        $this->assertReturnsSuccessAndSuggestsUrl($commandTester);
        $this->assertProducedWarning(
            $commandTester,
            'You will not be able to login as this user until the auth status is changed to "Active".'
        );
    }

    public function testExecuteDisplaysErrorIfUserDoesntExist()
    {
        $this->createMocks(null, null, null, true);

        $commandTester = $this->executeCommand();

        $this->assertProducedError(
            $commandTester,
            \sprintf('User with username "%s" does not exist.', self::USERNAME)
        );
    }

    public function testExecuteDisplaysErrorIfUnknownUserType()
    {
        $this->createMocks(null, null, null, null, $this->createMock(UserInterface::class));

        $commandTester = $this->executeCommand();

        $this->assertProducedError(
            $commandTester,
            \sprintf('Unsupported user type, the user "%s" cannot be impersonated.', self::USERNAME)
        );
    }

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->command = new ImpersonateUserCommand(
            $this->managerRegistry,
            $this->createMock(Router::class),
            $this->createMock(ConfigManager::class),
            $this->userManager,
            $this->createMock(DateTimeFormatterInterface::class)
        );
    }

    private function createMocks(
        bool $userEnabled = null,
        string $authStatusId = null,
        string $userClass = null,
        bool $nullUserStub = null,
        object $userStub = null
    ) {
        if (null === $userStub && true !== $nullUserStub) {
            $userStub = $this->getMockBuilder($userClass ?? User::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['isEnabled'])
                ->addMethods(['getAuthStatus'])
                ->getMock();
            $userStub->expects($this->any())
                ->method('isEnabled')
                ->willReturn($userEnabled ?? true);
            $userStub->expects($this->any())
                ->method('getAuthStatus')
                ->willReturn(new TestEnumValue(
                    $authStatusId ?? UserManager::STATUS_ACTIVE,
                    $authStatusId ?? UserManager::STATUS_ACTIVE
                ));
        }
        $this->userManager->expects($this->any())
            ->method('findUserByUsername')
            ->willReturn($userStub);

        $managerStub = $this->createMock(EntityManager::class);
        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($managerStub);
    }

    private function executeCommand(): CommandTester
    {
        return $this->doExecuteCommand($this->command, [
            'username' => self::USERNAME,
        ]);
    }

    private function assertReturnsSuccessAndSuggestsUrl(CommandTester $commandTester)
    {
        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, 'open the following URL');
    }
}
