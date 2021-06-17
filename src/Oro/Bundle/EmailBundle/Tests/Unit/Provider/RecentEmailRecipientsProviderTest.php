<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
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
    private $tokenAccessor;
    private $aclHelper;
    private $relatedEmailsProvider;
    private $registry;
    private $emailOwnerProvider;
    private $emailRecipientsHelper;

    private $emailRecipientsProvider;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->relatedEmailsProvider = $this->createMock(RelatedEmailsProvider::class);

        $em = $this->createMock(EntityManager::class);

        $this->registry = $this->createMock(Registry::class);
        $this->registry->expects($this->any())
            ->method('getManager')
            ->willReturn($em);

        $this->emailOwnerProvider = $this->createMock(EmailOwnerProvider::class);

        $this->emailRecipientsHelper = $this->createMock(EmailRecipientsHelper::class);

        $this->emailRecipientsProvider = new RecentEmailRecipientsProvider(
            $this->tokenAccessor,
            $this->relatedEmailsProvider,
            $this->aclHelper,
            $this->registry,
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

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:EmailRecipient')
            ->willReturn($emailRecipientRepository);

        $this->emailRecipientsHelper->expects($this->any())
            ->method('isObjectAllowed')
            ->willReturn(true);

        $this->assertEquals($expectedResult, $this->emailRecipientsProvider->getRecipients($args));
    }

    public function emailProvider()
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
