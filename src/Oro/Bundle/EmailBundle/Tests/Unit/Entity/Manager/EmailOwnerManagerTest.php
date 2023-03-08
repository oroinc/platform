<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmail;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity;
use Oro\Component\Testing\Unit\ORM\Mocks\UnitOfWorkMock;

class EmailOwnerManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EmailOwnerProviderStorage */
    private $emailOwnerProviderStorage;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EmailAddressManager */
    private $emailAddressManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EmailOwnerManager */
    private $emailOwnerManager;

    /** @var array */
    private $fixtures;

    protected function setUp(): void
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

        $emailOwnerProvider = $this->createMock(EmailOwnerProviderInterface::class);
        $emailOwnerProvider->expects($this->any())
            ->method('getEmailOwnerClass')
            ->willReturn(TestEmailOwner::class);

        $this->emailOwnerProviderStorage = $this->createMock(EmailOwnerProviderStorage::class);
        $this->emailOwnerProviderStorage->expects($this->any())
            ->method('getProviders')
            ->willReturn([$emailOwnerProvider, $emailOwnerProvider]);
        $this->emailOwnerProviderStorage->expects($this->any())
            ->method('getEmailOwnerFieldName')
            ->willReturnOnConsecutiveCalls('primaryEmail', 'homeEmail');

        $emailAddressRepository = $this->createMock(EntityRepository::class);
        $emailAddressRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturnCallback(function (array $criteria) {
                return $this->findEmailAddressBy($criteria['email']);
            });
        $emailAddressRepository->expects($this->any())
            ->method('findBy')
            ->willReturnCallback(function (array $criteria) {
                $keys = array_keys($criteria);
                $owner = $criteria[$keys[0]];

                $emailAddress = $this->findEmailAddressBy($owner->getId(), $keys[0]);
                if ($emailAddress) {
                    return [$emailAddress];
                }

                return [];
            });

        $em = $this->createMock(EntityManager::class);

        $this->emailAddressManager = $this->createMock(EmailAddressManager::class);
        $this->emailAddressManager->expects($this->any())
            ->method('getEmailAddressRepository')
            ->willReturn($emailAddressRepository);
        $this->emailAddressManager->expects($this->any())
            ->method('newEmailAddress')
            ->willReturn(new EmailAddress());
        $this->emailAddressManager->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

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

    public function handleChangedAddressesDataProvider(): array
    {
        $created1 = new TestEmailOwner(null, 'created1');
        $deleted1 = new TestEmailOwner(2, 'deleted1');

        return [
            [
                [
                    'updates' => [],
                    'deletions' => [],
                ],
                [[],[], []],
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
                    [
                        (new EmailAddress(1))
                            ->setOwner($created1)
                            ->setEmail('existing@example.com'),
                        (new EmailAddress(2))
                            ->setEmail('existing2@example.com')
                    ],
                    [
                        (new EmailAddress())
                            ->setOwner($created1)
                            ->setEmail('primary@example.com'),
                    ],
                    ['primary@example.com', 'existing@example.com', 'existing2@example.com']
                ],
            ],
        ];
    }

    /**
     * @dataProvider createEmailAddressDataProvider
     */
    public function testCreateEmailAddressData(UnitOfWorkMock $uow, $result)
    {
        $this->assertEquals($result, $this->emailOwnerManager->createEmailAddressData($uow));
    }

    public function createEmailAddressDataProvider(): array
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
                new UnitOfWorkMock(),
                ['updates' => [], 'deletions' => []],
            ],
            [
                (new UnitOfWorkMock())
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

    private function findEmailAddressBy($value, $key = 'email')
    {
        if (array_key_exists($key, $this->fixtures) && array_key_exists($value, $this->fixtures[$key])) {
            return $this->fixtures[$key][$value];
        }

        return null;
    }
}
