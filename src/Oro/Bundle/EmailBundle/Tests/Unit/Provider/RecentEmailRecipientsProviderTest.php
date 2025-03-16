<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RecentEmailRecipientsProviderTest extends TestCase
{
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private AclHelper&MockObject $aclHelper;
    private RelatedEmailsProvider&MockObject $relatedEmailsProvider;
    private ManagerRegistry&MockObject $doctrine;
    private EmailOwnerProvider&MockObject $emailOwnerProvider;
    private EmailRecipientsHelper&MockObject $emailRecipientsHelper;
    private RecentEmailRecipientsProvider $emailRecipientsProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->relatedEmailsProvider = $this->createMock(RelatedEmailsProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->emailOwnerProvider = $this->createMock(EmailOwnerProvider::class);
        $this->emailRecipientsHelper = $this->createMock(EmailRecipientsHelper::class);

        $this->doctrine->expects(self::any())
            ->method('getManager')
            ->willReturn($this->createMock(EntityManagerInterface::class));

        $this->emailRecipientsProvider = new RecentEmailRecipientsProvider(
            $this->tokenAccessor,
            $this->relatedEmailsProvider,
            $this->aclHelper,
            $this->doctrine,
            $this->emailOwnerProvider,
            $this->emailRecipientsHelper
        );
    }

    public function testGetSectionShouldReturnRecentEmailsSection(): void
    {
        self::assertEquals('oro.email.autocomplete.recently_used', $this->emailRecipientsProvider->getSection());
    }

    public function testGetRecipientsShouldReturnEmptyArrayIfUserIsNotLoggedIn(): void
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $args = new EmailRecipientsProviderArgs(null, '', 100);

        self::assertEmpty($this->emailRecipientsProvider->getRecipients($args));
    }

    /**
     * @dataProvider emailProvider
     */
    public function testGetRecipientsShouldReturnRecipients(
        EmailRecipientsProviderArgs $args,
        array $senderEmails,
        array $resultEmails,
        array $expectedResult
    ): void {
        $user = new User();

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->relatedEmailsProvider->expects(self::once())
            ->method('getEmails')
            ->with($user)
            ->willReturn($senderEmails);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::once())
            ->method('setMaxResults')
            ->with($args->getLimit())
            ->willReturnSelf();

        $emailRecipientRepository = $this->createMock(EmailRecipientRepository::class);
        $emailRecipientRepository->expects(self::once())
            ->method('getEmailsUsedInLast30DaysQb')
            ->with(array_keys($senderEmails), [], $args->getQuery())
            ->willReturn($qb);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($resultEmails);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(EmailRecipient::class)
            ->willReturn($emailRecipientRepository);

        $this->emailRecipientsHelper->expects(self::any())
            ->method('isObjectAllowed')
            ->willReturn(true);

        self::assertEquals($expectedResult, $this->emailRecipientsProvider->getRecipients($args));
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
