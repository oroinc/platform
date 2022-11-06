<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Provider\ParentMessageIdProvider;

class ParentMessageIdProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ParentMessageIdProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EmailRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Email::class)
            ->willReturn($this->repository);

        $this->provider = new ParentMessageIdProvider($doctrine);
    }

    public function testGetParentMessageIdToReplyReturnsNullWhenNoParentEmailId(): void
    {
        $emailModel = (new EmailModel())
            ->setMailType(EmailModel::MAIL_TYPE_REPLY);

        $this->repository->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->provider->getParentMessageIdToReply($emailModel));
    }

    public function testGetParentMessageIdToReplyReturnsNullWhenTypeNotReply(): void
    {
        $emailModel = (new EmailModel())
            ->setMailType(EmailModel::MAIL_TYPE_REPLY);

        $this->repository->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->provider->getParentMessageIdToReply($emailModel));
    }

    /**
     * @dataProvider getParentMessageIdToReplyDataProvider
     */
    public function testGetParentMessageIdToReply(?string $messageId): void
    {
        $parentEmailId = 42;
        $emailModel = (new EmailModel())
            ->setParentEmailId($parentEmailId)
            ->setMailType(EmailModel::MAIL_TYPE_REPLY);

        $this->repository->expects(self::once())
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
