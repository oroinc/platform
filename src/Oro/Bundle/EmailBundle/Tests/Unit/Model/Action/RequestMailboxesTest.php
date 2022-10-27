<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model\Action;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessProviderInterface;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Bundle\EmailBundle\Model\Action\RequestMailboxes;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RequestMailboxesTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var MailboxRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var RequestMailboxes */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->repository = $this->createMock(MailboxRepository::class);

        $demoProcess = $this->createMock(MailboxProcessProviderInterface::class);
        $demoProcess->expects($this->any())
            ->method('getSettingsEntityFQCN')
            ->willReturn('DemoProcessSettings');

        $mailboxProcessStorage = $this->createMock(MailboxProcessStorage::class);
        $mailboxProcessStorage->expects($this->any())
            ->method('getProcess')
            ->with('demo')
            ->willReturn($demoProcess);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with('OroEmailBundle:Mailbox')
            ->willReturn($this->repository);

        $this->action = new RequestMailboxes(
            $this->contextAccessor,
            $doctrine,
            $mailboxProcessStorage
        );
        $this->action->setDispatcher($this->createMock(EventDispatcherInterface::class));
    }

    public function testExecuteAction()
    {
        $options = [
            'process_type' => 'demo',
            'email' => '$.email',
            'attribute' => '$.attribute'
        ];

        $fakeContext = ['fake', 'things', 'are', 'here'];

        $email = new Email();

        $mailboxes = [
            new Mailbox()
        ];

        $this->contextAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap([
                [$fakeContext, 'demo', 'demo'],
                [$fakeContext, '$.email', $email]
            ]);
        $this->contextAccessor->expects($this->once())
            ->method('setValue')
            ->with($fakeContext, '$.attribute', $mailboxes);

        $this->repository->expects($this->once())
            ->method('findBySettingsClassAndEmail')
            ->with('DemoProcessSettings', $email)
            ->willReturn($mailboxes);

        $this->action->initialize($options);
        $this->action->execute($fakeContext);
    }
}
