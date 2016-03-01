<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressChecker;

class KnownEmailAddressCheckerTest extends \PHPUnit_Framework_TestCase
{
    const EMAIL_ADDRESS_PROXY_CLASS = 'Entity\TestEmailAddress';
    const TEST_CONTACT_CLASS = 'Entity\TestContact';
    const USER_CLASS = 'Oro\Bundle\UserBundle\Entity\User';
    const MAILBOX_CLASS = 'Oro\Bundle\EmailBundle\Entity\Mailbox';

    // @codingStandardsIgnoreStart
    const QB_SELECT = 'a.email,IDENTITY(a.userId) AS userId,IDENTITY(a.contactId) AS contactId,IDENTITY(a.mailboxId) AS mailboxId';
    // @codingStandardsIgnoreStop

    /** @var KnownEmailAddressChecker */
    private $checker;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailAddressManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailAddressRepository;

    protected function setUp()
    {
        $this->logger                 = $this->getMock('Psr\Log\LoggerInterface');
        $this->em                     = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailAddressManager    =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager')
                ->disableOriginalConstructor()
                ->getMock();
        $this->emailAddressRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailAddressManager->expects($this->any())
            ->method('getEmailAddressRepository')
            ->will($this->returnValue($this->emailAddressRepository));
        $this->emailAddressManager->expects($this->any())
            ->method('getEmailAddressProxyClass')
            ->will($this->returnValue(self::EMAIL_ADDRESS_PROXY_CLASS));

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

        $query        = $this->getLoadEmailAddressesQuery(
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
            ->will($this->returnValue($queryBuilder));

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

        $query        = $this->getLoadEmailAddressesQuery(
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
            ->will($this->returnSelf());

        $this->emailAddressRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->will($this->returnValue($queryBuilder));

        $checker->preLoadEmailAddresses(['user@test.com']);
        $this->assertTrue($checker->isAtLeastOneKnownEmailAddress('user@test.com'));
        $this->assertTrue($checker->isAtLeastOneUserEmailAddress(1, 'user@test.com'));
        $this->assertFalse($checker->isAtLeastOneUserEmailAddress(2, 'user@test.com'));
    }

    public function testIsAtLeastOneKnownEmailAddressSequence()
    {
        $query1        = $this->getLoadEmailAddressesQuery(
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

        $query2        = $this->getLoadEmailAddressesQuery(
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
            ->will($this->onConsecutiveCalls($queryBuilder1, $queryBuilder2));

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
    public function testIsAtLeastOneKnownEmailAddress($emailAddress, $emailsToLoad, $queryResult, $expected)
    {
        $query        = $this->getLoadEmailAddressesQuery(
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
            ->will($this->returnValue($queryBuilder));

        $this->checker->isAtLeastOneKnownEmailAddress($emailAddress);

        foreach ($expected as $email => $expectedResult) {
            $this->assertSame($expectedResult, $this->checker->isAtLeastOneKnownEmailAddress($email));
            // check that result is cached
            $this->assertSame($expectedResult, $this->checker->isAtLeastOneKnownEmailAddress($email));
        }
    }

    public function isAtLeastOneKnownEmailAddressProvider()
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
    public function testIsAtLeastOneUserEmailAddress($emailAddress, $emailsToLoad, $queryResult, $expected)
    {
        $query        = $this->getLoadEmailAddressesQuery(
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
            ->will($this->returnValue($queryBuilder));

        $this->checker->isAtLeastOneUserEmailAddress(1, $emailAddress);

        foreach ($expected as $email => $expectedResult) {
            $this->assertSame($expectedResult, $this->checker->isAtLeastOneUserEmailAddress(1, $email));
            // check that result is cached
            $this->assertSame($expectedResult, $this->checker->isAtLeastOneUserEmailAddress(1, $email));
            // check for other user
            $this->assertFalse($this->checker->isAtLeastOneUserEmailAddress(2, $email));
        }
    }

    public function isAtLeastOneUserEmailAddressProvider()
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

    /**
     * @return EmailOwnerProviderStorage
     */
    public function getEmailOwnerProviderStorage()
    {
        $userProvider = $this->getMock('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface');
        $userProvider->expects($this->any())
            ->method('getEmailOwnerClass')
            ->will($this->returnValue(self::USER_CLASS));

        $contactProvider = $this->getMock('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface');
        $contactProvider->expects($this->any())
            ->method('getEmailOwnerClass')
            ->will($this->returnValue(self::TEST_CONTACT_CLASS));

        $mailboxProvider = $this->getMock('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface');
        $mailboxProvider->expects($this->any())
            ->method('getEmailOwnerClass')
            ->will($this->returnValue(self::MAILBOX_CLASS));

        $emailOwnerProviderStorage =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage')
                ->disableOriginalConstructor()
                ->getMock();
        $emailOwnerProviderStorage->expects($this->any())
            ->method('getProviders')
            ->will($this->returnValue([$userProvider, $contactProvider, $mailboxProvider]));
        $emailOwnerProviderStorage->expects($this->any())
            ->method('getEmailOwnerFieldName')
            ->will(
                $this->returnValueMap(
                    [
                        [$userProvider, 'userId'],
                        [$contactProvider, 'contactId'],
                        [$mailboxProvider, 'mailboxId']
                    ]
                )
            );

        return $emailOwnerProviderStorage;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $query
     * @param array                                    $emailsToLoad
     * @param string                                   $select
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLoadEmailAddressesQueryBuilder(
        $query,
        $emailsToLoad,
        $select = self::QB_SELECT
    ) {
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with($select)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('a.hasOwner = :hasOwner AND a.email IN (:emails)')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(1))
            ->method('setParameter')
            ->with('hasOwner', true)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(2))
            ->method('setParameter')
            ->with('emails', $emailsToLoad)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        return $queryBuilder;
    }

    /**
     * @param array $result
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLoadEmailAddressesQuery($result)
    {
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($result));

        return $query;
    }
}
