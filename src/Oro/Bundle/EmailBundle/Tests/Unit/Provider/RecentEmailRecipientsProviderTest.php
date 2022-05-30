<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Provider\RecentEmailRecipientsProvider;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;

class RecentEmailRecipientsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var RelatedEmailsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $relatedEmailsProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EmailOwnerProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailOwnerProvider;

    /** @var EmailRecipientsHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $emailRecipientsHelper;

    /** @var RecentEmailRecipientsProvider */
    private $emailRecipientsProvider;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->relatedEmailsProvider = $this->createMock(RelatedEmailsProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->emailOwnerProvider = $this->createMock(EmailOwnerProvider::class);
        $this->emailRecipientsHelper = $this->createMock(EmailRecipientsHelper::class);

        $this->doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($this->createMock(EntityManager::class));

        $this->emailRecipientsProvider = new RecentEmailRecipientsProvider(
            $this->tokenAccessor,
            $this->relatedEmailsProvider,
            $this->aclHelper,
            $this->doctrine,
            $this->emailOwnerProvider,
            $this->emailRecipientsHelper
        );
    }

    public function testGetSectionShouldReturnRecentEmailsSection()
    {
        $this->assertEquals('oro.email.autocomplete.recently_used', $this->emailRecipientsProvider->getSection());
    }

    public function testGetRecipientsShouldReturnEmptyArrayIfUserIsNotLoggedIn()
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $args = new EmailRecipientsProviderArgs(null, '', 100);

        $this->assertEmpty($this->emailRecipientsProvider->getRecipients($args));
    }

    /**
     * @dataProvider emailProvider
     */
    public function testGetRecipientsShouldReturnRecipients(
        EmailRecipientsProviderArgs $args,
        array $senderEmails,
        array $resultEmails,
        array $expectedResult
    ) {
        $user = new User();

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->relatedEmailsProvider->expects($this->once())
            ->method('getEmails')
            ->with($user)
            ->willReturn($senderEmails);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with($args->getLimit())
            ->willReturnSelf();

        $emailRecipientRepository = $this->createMock(EmailRecipientRepository::class);
        $emailRecipientRepository->expects($this->once())
            ->method('getEmailsUsedInLast30DaysQb')
            ->with(array_keys($senderEmails), [], $args->getQuery())
            ->willReturn($qb);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($resultEmails);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(EmailRecipient::class)
            ->willReturn($emailRecipientRepository);

        $this->emailRecipientsHelper->expects($this->any())
            ->method('isObjectAllowed')
            ->willReturn(true);

        $this->assertEquals($expectedResult, $this->emailRecipientsProvider->getRecipients($args));
    }

    public function emailProvider(): array
    {
        return [
            [
                new EmailRecipientsProviderArgs(null, 'query', 100),
                ['sender@example.com' => 'Sender <sender@example.com>'],
                [
                    [
                        'email' => 'recent1@example.com',
                        'name'  => 'Recent1 <recent1@example.com>',
                    ],
                    [
                        'email' => 'recent2@example.com',
                        'name'  => 'Recent2 <recent2@example.com>',
                    ],
                ],
                [
                    new Recipient('recent1@example.com', 'Recent1 <recent1@example.com>'),
                    new Recipient('recent2@example.com', 'Recent2 <recent2@example.com>'),
                ]
            ],
        ];
    }
}
