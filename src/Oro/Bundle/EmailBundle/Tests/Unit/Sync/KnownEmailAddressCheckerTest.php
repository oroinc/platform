<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressChecker;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Psr\Log\LoggerInterface;

class KnownEmailAddressCheckerTest extends \PHPUnit\Framework\TestCase
{
    private const EMAIL_ADDRESS_PROXY_CLASS = 'Entity\TestEmailAddress';
    private const TEST_CONTACT_CLASS = 'Entity\TestContact';
    private const USER_CLASS = User::class;
    private const MAILBOX_CLASS = Mailbox::class;

    // @codingStandardsIgnoreStart
    private const QB_SELECT = 'a.email,IDENTITY(a.userId) AS userId,IDENTITY(a.contactId) AS contactId,IDENTITY(a.mailboxId) AS mailboxId';
    // @codingStandardsIgnoreStop

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EmailAddressManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAddressManager;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAddressRepository;

    /** @var KnownEmailAddressChecker */
    private $checker;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->emailAddressManager = $this->createMock(EmailAddressManager::class);
        $this->emailAddressRepository = $this->createMock(EntityRepository::class);
        $this->emailAddressManager->expects($this->any())
            ->method('getEmailAddressRepository')
            ->willReturn($this->emailAddressRepository);
        $this->emailAddressManager->expects($this->any())
            ->method('getEmailAddressProxyClass')
            ->willReturn(self::EMAIL_ADDRESS_PROXY_CLASS);

        $this->checker = new KnownEmailAddressChecker(
            $this->em,
            $this->emailAddressManager,
            new EmailAddressHelper(),
            $this->getEmailOwnerProviderStorage(),
            []
        );
        $this->checker->setLogger($this->logger);
    }

    public function testCheckEmailAddressWithUserExclusion()
    {
        $emailOwnerProviderStorage = $this->getEmailOwnerProviderStorage();

        $checker = new KnownEmailAddressChecker(
            $this->em,
            $this->emailAddressManager,
            new EmailAddressHelper(),
            $emailOwnerProviderStorage,
            [self::USER_CLASS]
        );
        $checker->setLogger($this->logger);

        $query = $this->getLoadEmailAddressesQuery(
            [
                ['email' => 'contact@test.com', 'userId' => null, 'contactId' => 10, 'mailboxId' => null],
                ['email' => 'user@test.com', 'userId' => 1, 'contactId' => null, 'mailboxId' => null],
            ]
        );
        $queryBuilder = $this->getLoadEmailAddressesQueryBuilder(
            $query,
            [
                'contact@test.com' => 'contact@test.com',
                'user@test.com'    => 'user@test.com'
            ]
        );
        $queryBuilder->expects($this->never())
            ->method('andWhere');

        $this->emailAddressRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder);

        $checker->preLoadEmailAddresses(['contact@test.com', 'user@test.com']);
        $this->assertTrue($checker->isAtLeastOneKnownEmailAddress('contact@test.com'));
        $this->assertFalse($checker->isAtLeastOneKnownEmailAddress('user@test.com'));
        $this->assertFalse($checker->isAtLeastOneUserEmailAddress(1, 'contact@test.com'));
        $this->assertTrue($checker->isAtLeastOneUserEmailAddress(1, 'user@test.com'));
        $this->assertFalse($checker->isAtLeastOneUserEmailAddress(2, 'user@test.com'));
    }

    public function testCheckEmailAddressWithContactExclusion()
    {
        $emailOwnerProviderStorage = $this->getEmailOwnerProviderStorage();

        $checker = new KnownEmailAddressChecker(
            $this->em,
            $this->emailAddressManager,
            new EmailAddressHelper(),
            $emailOwnerProviderStorage,
            [self::TEST_CONTACT_CLASS]
        );
        $checker->setLogger($this->logger);

        $query = $this->getLoadEmailAddressesQuery(
            [
                ['email' => 'user@test.com', 'userId' => 1, 'mailboxId' => null]
            ]
        );
        $queryBuilder = $this->getLoadEmailAddressesQueryBuilder(
            $query,
            [
                'user@test.com' => 'user@test.com'
            ],
            'a.email,IDENTITY(a.userId) AS userId,IDENTITY(a.mailboxId) AS mailboxId'
        );
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('a.contactId IS NULL')
            ->willReturnSelf();

        $this->emailAddressRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder);

        $checker->preLoadEmailAddresses(['user@test.com']);
        $this->assertTrue($checker->isAtLeastOneKnownEmailAddress('user@test.com'));
        $this->assertTrue($checker->isAtLeastOneUserEmailAddress(1, 'user@test.com'));
        $this->assertFalse($checker->isAtLeastOneUserEmailAddress(2, 'user@test.com'));
    }

    public function testIsAtLeastOneKnownEmailAddressSequence()
    {
        $query1 = $this->getLoadEmailAddressesQuery(
            [
                ['email' => '1@test.com', 'userId' => null, 'contactId' => 1, 'mailboxId' => null],
                ['email' => '3@test.com', 'userId' => null, 'contactId' => 2, 'mailboxId' => null],
            ]
        );
        $queryBuilder1 = $this->getLoadEmailAddressesQueryBuilder(
            $query1,
            [
                '1@test.com' => '1@test.com',
                '2@test.com' => '2@test.com',
                '3@test.com' => '3@test.com',
                '4@test.com' => '4@test.com'
            ]
        );
        $queryBuilder1->expects($this->never())
            ->method('andWhere');

        $query2 = $this->getLoadEmailAddressesQuery(
            [
                ['email' => '11@test.com', 'userId' => null, 'contactId' => 3, 'mailboxId' => null],
            ]
        );
        $queryBuilder2 = $this->getLoadEmailAddressesQueryBuilder(
            $query2,
            [
                '10@test.com' => '10@test.com',
                '11@test.com' => '11@test.com'
            ]
        );
        $queryBuilder2->expects($this->never())
            ->method('andWhere');

        $this->emailAddressRepository->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($queryBuilder1, $queryBuilder2);

        $this->assertTrue(
            $this->checker->isAtLeastOneKnownEmailAddress(
                '1@test.com',
                ['2@test.com', '3@test.com'],
                ['2@test.com', '4@test.com']
            )
        );
        $this->assertTrue(
            $this->checker->isAtLeastOneKnownEmailAddress('1@test.com')
        );
        $this->assertFalse(
            $this->checker->isAtLeastOneKnownEmailAddress('2@test.com')
        );
        $this->assertTrue(
            $this->checker->isAtLeastOneKnownEmailAddress('2@test.com', '10@test.com', '11@test.com')
        );
        $this->assertFalse(
            $this->checker->isAtLeastOneKnownEmailAddress('2@test.com', '10@test.com')
        );
        $this->assertTrue(
            $this->checker->isAtLeastOneKnownEmailAddress('2@test.com', '20@test.com', '1@test.com')
        );
    }

    /**
     * @dataProvider isAtLeastOneKnownEmailAddressProvider
     */
    public function testIsAtLeastOneKnownEmailAddress(
        array|string $emailAddress,
        array $emailsToLoad,
        array $queryResult,
        array $expected
    ) {
        $query = $this->getLoadEmailAddressesQuery(
            $queryResult
        );
        $queryBuilder = $this->getLoadEmailAddressesQueryBuilder(
            $query,
            $emailsToLoad
        );
        $queryBuilder->expects($this->never())
            ->method('andWhere');

        $this->emailAddressRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder);

        $this->checker->isAtLeastOneKnownEmailAddress($emailAddress);

        foreach ($expected as $email => $expectedResult) {
            $this->assertSame($expectedResult, $this->checker->isAtLeastOneKnownEmailAddress($email));
            // check that result is cached
            $this->assertSame($expectedResult, $this->checker->isAtLeastOneKnownEmailAddress($email));
        }
    }

    public function isAtLeastOneKnownEmailAddressProvider(): array
    {
        return [
            [
                'emailAddress' => '1@test.com',
                'emailsToLoad' => ['1@test.com' => '1@test.com'],
                'queryResult'  => [
                    ['email' => '1@test.com', 'userId' => null, 'contactId' => 1, 'mailboxId' => null]
                ],
                'expected'     => [
                    '1@test.com' => true
                ]
            ],
            [
                'emailAddress' => ['1@test.com', '2@test.com', '3@test.com', '', null],
                'emailsToLoad' => [
                    '1@test.com' => '1@test.com',
                    '2@test.com' => '2@test.com',
                    '3@test.com' => '3@test.com'
                ],
                'queryResult'  => [
                    ['email' => '1@test.com', 'userId' => 1, 'contactId' => null, 'mailboxId' => null],
                    ['email' => '2@test.com', 'userId' => null, 'contactId' => 10, 'mailboxId' => null]
                ],
                'expected'     => [
                    '1@test.com' => true,
                    '2@test.com' => true,
                    '3@test.com' => false
                ]
            ],
        ];
    }

    /**
     * @dataProvider isAtLeastOneUserEmailAddressProvider
     */
    public function testIsAtLeastOneUserEmailAddress(
        array|string $emailAddress,
        array $emailsToLoad,
        array $queryResult,
        array $expected
    ) {
        $query = $this->getLoadEmailAddressesQuery(
            $queryResult
        );
        $queryBuilder = $this->getLoadEmailAddressesQueryBuilder(
            $query,
            $emailsToLoad
        );
        $queryBuilder->expects($this->never())
            ->method('andWhere');

        $this->emailAddressRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->willReturn($queryBuilder);

        $this->checker->isAtLeastOneUserEmailAddress(1, $emailAddress);

        foreach ($expected as $email => $expectedResult) {
            $this->assertSame($expectedResult, $this->checker->isAtLeastOneUserEmailAddress(1, $email));
            // check that result is cached
            $this->assertSame($expectedResult, $this->checker->isAtLeastOneUserEmailAddress(1, $email));
            // check for other user
            $this->assertFalse($this->checker->isAtLeastOneUserEmailAddress(2, $email));
        }
    }

    public function isAtLeastOneUserEmailAddressProvider(): array
    {
        return [
            [
                'emailAddress' => '1@test.com',
                'emailsToLoad' => ['1@test.com' => '1@test.com'],
                'queryResult'  => [
                    ['email' => '1@test.com', 'userId' => 1, 'contactId' => null, 'mailboxId' => null]
                ],
                'expected'     => [
                    '1@test.com' => true
                ]
            ],
            [
                'emailAddress' => ['1@test.com', '2@test.com', '3@test.com', '', null],
                'emailsToLoad' => [
                    '1@test.com' => '1@test.com',
                    '2@test.com' => '2@test.com',
                    '3@test.com' => '3@test.com'
                ],
                'queryResult'  => [
                    ['email' => '1@test.com', 'userId' => 1, 'contactId' => null, 'mailboxId' => null],
                    ['email' => '2@test.com', 'userId' => null, 'contactId' => 10, 'mailboxId' => null]
                ],
                'expected'     => [
                    '1@test.com' => true,
                    '2@test.com' => false,
                    '3@test.com' => false
                ]
            ],
        ];
    }

    private function getEmailOwnerProviderStorage(): EmailOwnerProviderStorage
    {
        $userProvider = $this->createMock(EmailOwnerProviderInterface::class);
        $userProvider->expects($this->any())
            ->method('getEmailOwnerClass')
            ->willReturn(self::USER_CLASS);

        $contactProvider = $this->createMock(EmailOwnerProviderInterface::class);
        $contactProvider->expects($this->any())
            ->method('getEmailOwnerClass')
            ->willReturn(self::TEST_CONTACT_CLASS);

        $mailboxProvider = $this->createMock(EmailOwnerProviderInterface::class);
        $mailboxProvider->expects($this->any())
            ->method('getEmailOwnerClass')
            ->willReturn(self::MAILBOX_CLASS);

        $emailOwnerProviderStorage = $this->createMock(EmailOwnerProviderStorage::class);
        $emailOwnerProviderStorage->expects($this->any())
            ->method('getProviders')
            ->willReturn([$userProvider, $contactProvider, $mailboxProvider]);
        $emailOwnerProviderStorage->expects($this->any())
            ->method('getEmailOwnerFieldName')
            ->willReturnMap([
                [$userProvider, 'userId'],
                [$contactProvider, 'contactId'],
                [$mailboxProvider, 'mailboxId']
            ]);

        return $emailOwnerProviderStorage;
    }

    /**
     * @return QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getLoadEmailAddressesQueryBuilder(
        AbstractQuery $query,
        array $emailsToLoad,
        string $select = self::QB_SELECT
    ) {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with($select)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('a.hasOwner = :hasOwner AND a.email IN (:emails)')
            ->willReturnSelf();
        $queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(
                ['hasOwner', true],
                ['emails', $emailsToLoad]
            )
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        return $queryBuilder;
    }

    private function getLoadEmailAddressesQuery(array $result): AbstractQuery
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($result);

        return $query;
    }
}
