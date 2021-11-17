<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Provider\ParentMessageIdProvider;

class ParentMessageIdProviderTest extends \PHPUnit\Framework\TestCase
{
    private ParentMessageIdProvider $provider;

    private EmailRepository|\PHPUnit\Framework\MockObject\MockObject $repository;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->provider = new ParentMessageIdProvider($managerRegistry);

        $this->repository = $this->createMock(EmailRepository::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(Email::class)
            ->willReturn($this->repository);
    }

    public function testGetParentMessageIdToReplyReturnsNullWhenNoParentEmailId(): void
    {
        $emailModel = (new EmailModel())
            ->setMailType(EmailModel::MAIL_TYPE_REPLY);

        $this->repository
            ->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->provider->getParentMessageIdToReply($emailModel));
    }

    public function testGetParentMessageIdToReplyReturnsNullWhenTypeNotReply(): void
    {
        $emailModel = (new EmailModel())
            ->setMailType(EmailModel::MAIL_TYPE_REPLY);

        $this->repository
            ->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->provider->getParentMessageIdToReply($emailModel));
    }

    /**
     * @dataProvider getParentMessageIdToReplyDataProvider
     *
     * @param string|null $messageId
     */
    public function testGetParentMessageIdToReply(?string $messageId): void
    {
        $parentEmailId = 42;
        $emailModel = (new EmailModel())
            ->setParentEmailId($parentEmailId)
            ->setMailType(EmailModel::MAIL_TYPE_REPLY);

        $this->repository
            ->expects(self::once())
            ->method('findMessageIdByEmailId')
            ->with($parentEmailId)
            ->willReturn($messageId);

        self::assertSame($messageId, $this->provider->getParentMessageIdToReply($emailModel));
    }

    public function getParentMessageIdToReplyDataProvider(): array
    {
        return [
            'message id not found' => ['messageId' => null],
            'message id found' => ['messageId' => 'sample/message/id@example.com'],
        ];
    }
}
