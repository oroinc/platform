<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmail;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity;
use Oro\Component\TestUtils\ORM\Mocks\UnitOfWork;

class EmailOwnerManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|EmailOwnerProviderStorage */
    protected $emailOwnerProviderStorage;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EmailAddressManager */
    protected $emailAddressManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EmailOwnerManager */
    protected $emailOwnerManager;

    /** @var array */
    protected $fixtures;

    public function setUp()
    {
        $this->fixtures = [
            'primaryEmail' => [
                1 => (new EmailAddress(1))
                    ->setEmail('existing@example.com'),
                2 => (new EmailAddress(2))
                    ->setEmail('existing2@example.com'),
            ],
            'email' => [
                'existing@example.com' => (new EmailAddress(1))
                    ->setEmail('existing@example.com'),
                'existing2@example.com' => (new EmailAddress(2))
                    ->setEmail('existing2@example.com'),
            ]
        ];

        $emailOwnerProvider = $this->getMock('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface');
        $emailOwnerProvider
            ->expects($this->any())
            ->method('getEmailOwnerClass')
            ->will($this->returnValue('Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner'));

        $this->emailOwnerProviderStorage =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage')
                ->disableOriginalConstructor()
                ->getMock();
        $this->emailOwnerProviderStorage->expects($this->any())
            ->method('getProviders')
            ->will($this->returnValue([$emailOwnerProvider, $emailOwnerProvider]));
        $this->emailOwnerProviderStorage->expects($this->any())
            ->method('getEmailOwnerFieldName')
            ->will($this->onConsecutiveCalls('primaryEmail', 'homeEmail'));

        $emailAddressRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $emailAddressRepository->expects($this->any())
            ->method('findOneBy')
            ->will($this->returnCallback(function (array $criteria) {
                return $this->findEmailAddressBy($criteria['email']);
            }));
        $emailAddressRepository->expects($this->any())
            ->method('findBy')
            ->will($this->returnCallback(function (array $criteria) {
                $keys = array_keys($criteria);
                $owner = $criteria[$keys[0]];

                $emailAddress = $this->findEmailAddressBy($owner->getId(), $keys[0]);
                if ($emailAddress) {
                    return [$emailAddress];
                }

                return [];
            }));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailAddressManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailAddressManager->expects($this->any())
            ->method('getEmailAddressRepository')
            ->will($this->returnValue($emailAddressRepository));
        $this->emailAddressManager->expects($this->any())
            ->method('newEmailAddress')
            ->will($this->returnValue(new EmailAddress()));
        $this->emailAddressManager->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->emailOwnerManager = new EmailOwnerManager(
            $this->emailOwnerProviderStorage,
            $this->emailAddressManager
        );
    }

    /**
     * @dataProvider handleChangedAddressesDataProvider
     */
    public function testHandleChangedAddresses(array $emailAddressData, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->emailOwnerManager->handleChangedAddresses($emailAddressData));
    }

    public function handleChangedAddressesDataProvider()
    {
        $created1 = new TestEmailOwner(null, 'created1');
        $deleted1 = new TestEmailOwner(2, 'deleted1');

        return [
            [
                [
                    'updates' => [],
                    'deletions' => [],
                ],
                [],
            ],
            [
                [
                    'updates' => [
                        spl_object_hash($created1) => [
                            'entity' => $created1,
                            'changeSet' => [
                                'firstName' => [null, 'created1'],
                                'primaryEmail' => [null, 'primary@example.com'],
                                'homeEmail'    => [null, 'existing@example.com'],
                            ],
                        ],
                    ],
                    'deletions' => [
                        $deleted1
                    ],
                ],
                [
                    (new EmailAddress())
                        ->setOwner($created1)
                        ->setEmail('primary@example.com'),
                    (new EmailAddress(1))
                        ->setOwner($created1)
                        ->setEmail('existing@example.com'),
                    (new EmailAddress(2))
                        ->setEmail('existing2@example.com')
                ],
            ],
        ];
    }

    /**
     * @dataProvider createEmailAddressDataProvider
     */
    public function testCreateEmailAddressData(UnitOfWork $uow, $result)
    {
        $this->assertEquals($result, $this->emailOwnerManager->createEmailAddressData($uow));
    }

    public function createEmailAddressDataProvider()
    {
        $created1 = new TestEmailOwner(null, 'created1');
        $created2 = new TestEmailOwner(null, 'created2');
        $created3 = new TestEmail();
        $updated1 = new TestEmailOwner(1, 'updated1');
        $updated2 = new TestEmail(2);
        $deleted1 = new TestEmailOwner(2, 'deleted1');
        $deleted2 = new TestEmail(3);

        return [
            [
                new UnitOfWork(),
                ['updates' => [], 'deletions' => []],
            ],
            [
                (new UnitOfWork())
                    ->addInsertion($created1, ['firstName' => [null, 'created1']])
                    ->addInsertion($created2, ['firstName' => [null, 'created2']])
                    ->addInsertion($created3)
                    ->addInsertion(new SomeEntity())
                    ->addUpdate($updated1, ['firstName' => ['oldName', 'updated1']])
                    ->addUpdate($updated2)
                    ->addUpdate(new SomeEntity())
                    ->addDeletion($deleted1)
                    ->addDeletion($deleted2)
                    ->addDeletion(new SomeEntity()),
                [
                    'updates' => [
                        spl_object_hash($created1) => [
                            'entity' => $created1,
                            'changeSet' => ['firstName' => [null, 'created1']],
                        ],
                        spl_object_hash($created2) => [
                            'entity' => $created2,
                            'changeSet' => ['firstName' => [null, 'created2']],
                        ],
                        spl_object_hash($created3) => [
                            'entity' => $created3,
                            'changeSet' => [],
                        ],
                        spl_object_hash($updated1) => [
                            'entity' => $updated1,
                            'changeSet' => ['firstName' => ['oldName', 'updated1']],
                        ],
                        spl_object_hash($updated2) => [
                            'entity' => $updated2,
                            'changeSet' => [],
                        ],
                    ],
                    'deletions' => [
                        spl_object_hash($deleted1) => $deleted1,
                        spl_object_hash($deleted2) => $deleted2,
                    ],
                ],
            ],
        ];
    }

    protected function findEmailAddressBy($value, $key = 'email')
    {
        if (array_key_exists($key, $this->fixtures) && array_key_exists($value, $this->fixtures[$key])) {
            return $this->fixtures[$key][$value];
        }

        return null;
    }
}
