<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityManagerInterface;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailOwnerManagerTest extends TestCase
{
    private array $fixtures;
    private EmailOwnerProviderStorage&MockObject $emailOwnerProviderStorage;
    private EmailAddressManager&MockObject $emailAddressManager;
    private EmailOwnerManager $emailOwnerManager;

    #[\Override]
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
        $emailOwnerProvider->expects(self::any())
            ->method('getEmailOwnerClass')
            ->willReturn(TestEmailOwner::class);

        $this->emailOwnerProviderStorage = $this->createMock(EmailOwnerProviderStorage::class);
        $this->emailOwnerProviderStorage->expects(self::any())
            ->method('getProviders')
            ->willReturn([$emailOwnerProvider, $emailOwnerProvider]);
        $this->emailOwnerProviderStorage->expects(self::any())
            ->method('getEmailOwnerFieldName')
            ->willReturnOnConsecutiveCalls('primaryEmail', 'homeEmail');

        $emailAddressRepository = $this->createMock(EntityRepository::class);
        $emailAddressRepository->expects(self::any())
            ->method('findOneBy')
            ->willReturnCallback(function (array $criteria) {
                return $this->findEmailAddressBy($criteria['email']);
            });
        $emailAddressRepository->expects(self::any())
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

        $em = $this->createMock(EntityManagerInterface::class);

        $this->emailAddressManager = $this->createMock(EmailAddressManager::class);
        $this->emailAddressManager->expects(self::any())
            ->method('getEmailAddressRepository')
            ->willReturn($emailAddressRepository);
        $this->emailAddressManager->expects(self::any())
            ->method('newEmailAddress')
            ->willReturn(new EmailAddress());
        $this->emailAddressManager->expects(self::any())
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
    public function testHandleChangedAddresses(array $emailAddressData, string|array $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->emailOwnerManager->handleChangedAddresses($emailAddressData));
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
    public function testCreateEmailAddressData(UnitOfWorkMock $uow, array $result): void
    {
        self::assertEquals($result, $this->emailOwnerManager->createEmailAddressData($uow));
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
