<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration;
use Oro\Bundle\EmailBundle\Form\Handler\UserEmailConfigHandler;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\FormInterface;

class UserEmailConfigHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigChangeSet|\PHPUnit\Framework\MockObject\MockObject */
    private $changeSet;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var UserEmailConfigHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->changeSet = $this->createMock(ConfigChangeSet::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->handler = new UserEmailConfigHandler($this->entityManager);
    }

    public function testHandleWithoutMailboxForm()
    {
        $this->form->expects($this->once())
            ->method('has')
            ->with($this->getConfigKey())
            ->willReturn(false);

        $this->handler->handle($this->configManager, $this->changeSet, $this->form);
    }

    /**
     * @dataProvider getHandleWithMailboxDataProvider
     */
    public function testHandleWithMailboxData(mixed $data)
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

    public function testHandle()
    {
        $user = new User();
        $data = [
            'value' => $user
        ];
        $this->mockForm($data);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->handler->handle($this->configManager, $this->changeSet, $this->form);
    }

    private function getConfigKey(): string
    {
        return Configuration::getConfigKeyByName('user_mailbox', ConfigManager::SECTION_VIEW_SEPARATOR);
    }

    private function mockForm(mixed $data): void
    {
        $mailboxForm = $this->createMock(FormInterface::class);
        $mailboxForm->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $this->form->expects($this->once())
            ->method('has')
            ->with($this->getConfigKey())
            ->willReturn(true);
        $this->form->expects($this->once())
            ->method('get')
            ->with($this->getConfigKey())
            ->willReturn($mailboxForm);
    }
}
