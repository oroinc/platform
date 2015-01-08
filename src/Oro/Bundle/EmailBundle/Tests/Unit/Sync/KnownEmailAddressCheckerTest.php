<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Sync\KnownEmailAddressChecker;

class KnownEmailAddressCheckerTest extends \PHPUnit_Framework_TestCase
{
    const EMAIL_ADDRESS_PROXY_CLASS = 'Entity\Test';

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
        $this->emailAddressManager    = $this->getMockBuilder(
            'Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager'
        )
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
            new EmailOwnerProviderStorage(),
            []
        );
        $this->checker->setLogger($this->logger);
    }

    public function testIsAtLeastOneKnownEmailAddressWithExclusions()
    {
        $emailAddress = '1@test.com';

        $provider = $this->getMock('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface');
        $emailOwnerProviderStorage = new EmailOwnerProviderStorage();
        $emailOwnerProviderStorage->addProvider($provider);

        $provider->expects($this->once())
            ->method('getEmailOwnerClass')
            ->will($this->returnValue('Test\Exclude1'));

        $checker = new KnownEmailAddressChecker(
            $this->em,
            $this->emailAddressManager,
            new EmailAddressHelper(),
            $emailOwnerProviderStorage,
            ['Test\Exclude1']
        );
        $checker->setLogger($this->logger);

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getArrayResult'))
            ->getMockForAbstractClass();

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->at(0))
            ->method('select')
            ->with('a.email')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(1))
            ->method('where')
            ->with('a.hasOwner = :hasOwner AND a.email IN (:emails)')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(2))
            ->method('setParameter')
            ->with('hasOwner', true)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(3))
            ->method('setParameter')
            ->with('emails', [$emailAddress => $emailAddress])
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('a.owner1 IS NULL')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $this->emailAddressRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->will($this->returnValue($queryBuilder));

        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue([['email' => $emailAddress]]));

        $this->assertTrue($checker->isAtLeastOneKnownEmailAddress($emailAddress));
    }

    /**
     * @dataProvider emailAddressProvider
     */
    public function testIsAtLeastOneKnownEmailAddress(
        $emailAddress,
        $setParameterArg,
        $result,
        $expected
    ) {
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getArrayResult'))
            ->getMockForAbstractClass();

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('select', 'where', 'setParameter', 'getQuery'))
            ->getMock();
        $queryBuilder->expects($this->at(0))
            ->method('select')
            ->with('a.email')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(1))
            ->method('where')
            ->with('a.hasOwner = :hasOwner AND a.email IN (:emails)')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(2))
            ->method('setParameter')
            ->with('hasOwner', true)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(3))
            ->method('setParameter')
            ->with('emails', $setParameterArg)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->at(4))
            ->method('getQuery')
            ->will($this->returnValue($query));

        $this->emailAddressRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('a')
            ->will($this->returnValue($queryBuilder));

        $query->expects($this->once())
            ->method('getArrayResult')
            ->will($this->returnValue($result));

        $this->checker->isAtLeastOneKnownEmailAddress($emailAddress);

        foreach ($expected as $email => $expectedResult) {
            if ($expectedResult) {
                $this->assertTrue($this->checker->isAtLeastOneKnownEmailAddress($email));
                // check that result is cached
                $this->assertTrue($this->checker->isAtLeastOneKnownEmailAddress($email));
            } else {
                $this->assertFalse($this->checker->isAtLeastOneKnownEmailAddress($email));
                // check that result is cached
                $this->assertFalse($this->checker->isAtLeastOneKnownEmailAddress($email));
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testIsAtLeastOneKnownEmailAddressSequence()
    {
        $query1 = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getArrayResult'))
            ->getMockForAbstractClass();
        $queryBuilder1 = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('select', 'where', 'setParameter', 'getQuery'))
            ->getMock();
        $queryBuilder1->expects($this->at(0))
            ->method('select')
            ->with('a.email')
            ->will($this->returnSelf());
        $queryBuilder1->expects($this->at(1))
            ->method('where')
            ->with('a.hasOwner = :hasOwner AND a.email IN (:emails)')
            ->will($this->returnSelf());
        $queryBuilder1->expects($this->at(2))
            ->method('setParameter')
            ->with('hasOwner', true)
            ->will($this->returnSelf());
        $queryBuilder1->expects($this->at(3))
            ->method('setParameter')
            ->with(
                'emails',
                [
                    '1@test.com' => '1@test.com',
                    '2@test.com' => '2@test.com',
                    '3@test.com' => '3@test.com',
                    '4@test.com' => '4@test.com',
                ]
            )
            ->will($this->returnSelf());
        $queryBuilder1->expects($this->at(4))
            ->method('getQuery')
            ->will($this->returnValue($query1));

        $query2 = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getArrayResult'))
            ->getMockForAbstractClass();
        $queryBuilder2 = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('select', 'where', 'setParameter', 'getQuery'))
            ->getMock();
        $queryBuilder2->expects($this->at(0))
            ->method('select')
            ->with('a.email')
            ->will($this->returnSelf());
        $queryBuilder2->expects($this->at(1))
            ->method('where')
            ->with('a.hasOwner = :hasOwner AND a.email IN (:emails)')
            ->will($this->returnSelf());
        $queryBuilder2->expects($this->at(2))
            ->method('setParameter')
            ->with('hasOwner', true)
            ->will($this->returnSelf());
        $queryBuilder2->expects($this->at(3))
            ->method('setParameter')
            ->with('emails', ['10@test.com' => '10@test.com', '11@test.com' => '11@test.com'])
            ->will($this->returnSelf());
        $queryBuilder2->expects($this->at(4))
            ->method('getQuery')
            ->will($this->returnValue($query2));

        $this->emailAddressRepository->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->will($this->onConsecutiveCalls($queryBuilder1, $queryBuilder2));

        $query1->expects($this->once())
            ->method('getArrayResult')
            ->will(
                $this->returnValue(
                    [
                        ['email' => '1@test.com'],
                        ['email' => '3@test.com'],
                    ]
                )
            );
        $query2->expects($this->once())
            ->method('getArrayResult')
            ->will(
                $this->returnValue(
                    [
                        ['email' => '11@test.com'],
                    ]
                )
            );

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

    public function emailAddressProvider()
    {
        return [
            [
                '1@test.com',
                ['1@test.com' => '1@test.com'],
                [['email' => '1@test.com']],
                ['1@test.com' => true]
            ],
            [
                ['1@test.com', '2@test.com', '', null],
                ['1@test.com' => '1@test.com', '2@test.com' => '2@test.com'],
                [['email' => '1@test.com']],
                ['1@test.com' => true, '2@test.com' => false]
            ],
        ];
    }
}
