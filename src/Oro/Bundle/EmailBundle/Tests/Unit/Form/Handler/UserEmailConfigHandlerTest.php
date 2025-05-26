<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Form\Handler\UserEmailConfigHandler;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

class UserEmailConfigHandlerTest extends TestCase
{
    private const string FORM_FIELD_NAME = 'oro_email___user_mailbox';

    private ConfigManager&MockObject $configManager;
    private ConfigChangeSet&MockObject $changeSet;
    private FormInterface&MockObject $form;
    private EntityManagerInterface&MockObject $entityManager;
    private UserEmailConfigHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->changeSet = $this->createMock(ConfigChangeSet::class);
        $this->form = $this->createMock(FormInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($this->entityManager);

        $this->handler = new UserEmailConfigHandler($doctrine);
    }

    public function testHandleWithoutMailboxForm(): void
    {
        $this->form->expects(self::once())
            ->method('has')
            ->with(self::FORM_FIELD_NAME)
            ->willReturn(false);

        $this->handler->handle($this->configManager, $this->changeSet, $this->form);
    }

    /**
     * @dataProvider getHandleWithMailboxDataProvider
     */
    public function testHandleWithMailboxData(mixed $data): void
    {
        $this->mockForm($data);

        $this->handler->handle($this->configManager, $this->changeSet, $this->form);
    }

    public function getHandleWithMailboxDataProvider(): array
    {
        return [
            'empty data' => [
                null
            ],
            'not array' => [
                new \stdClass()
            ],
            'empty array' => [
                []
            ],
            'without value key' => [
                ['key' => 'value']
            ],
            'without user instance' => [
                ['value' => new \stdClass()]
            ]
        ];
    }

    public function testHandle(): void
    {
        $user = new User();
        $data = [
            'value' => $user
        ];
        $this->mockForm($data);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with($user);
        $this->entityManager->expects(self::once())
            ->method('flush');

        $this->handler->handle($this->configManager, $this->changeSet, $this->form);
    }

    private function mockForm(mixed $data): void
    {
        $mailboxForm = $this->createMock(FormInterface::class);
        $mailboxForm->expects(self::once())
            ->method('getData')
            ->willReturn($data);

        $this->form->expects(self::once())
            ->method('has')
            ->with(self::FORM_FIELD_NAME)
            ->willReturn(true);
        $this->form->expects(self::once())
            ->method('get')
            ->with(self::FORM_FIELD_NAME)
            ->willReturn($mailboxForm);
    }
}
