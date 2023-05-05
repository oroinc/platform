<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Model\CategorizedRecipient;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Model\RecipientEntity;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Tests\Unit\Stub\AddressStub;
use Oro\Bundle\EmailBundle\Tests\Unit\Stub\CustomerStub;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ReflectionProperty;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmailRecipientsHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var DQLNameFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $dqlNameFormatter;

    /** @var NameFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $nameFormatter;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var EmailOwnerProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailOwnerProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EmailAddressHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $addressHelper;

    /** @var EmailRecipientsHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $emailRecipientsHelper;

    /** @var Indexer|\PHPUnit\Framework\MockObject\MockObject */
    private $indexer;

    protected function setUp(): void
    {
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->dqlNameFormatter = $this->createMock(DQLNameFormatter::class);
        $this->nameFormatter = $this->createMock(NameFormatter::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->emailOwnerProvider = $this->createMock(EmailOwnerProvider::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->addressHelper = $this->createMock(EmailAddressHelper::class);
        $this->indexer = $this->createMock(Indexer::class);

        $this->emailRecipientsHelper = new EmailRecipientsHelper(
            $this->aclHelper,
            $this->dqlNameFormatter,
            $this->nameFormatter,
            $this->configManager,
            $this->translator,
            $this->emailOwnerProvider,
            $this->registry,
            $this->addressHelper,
            $this->indexer,
            PropertyAccess::createPropertyAccessor()
        );
    }

    /**
     * @dataProvider getRecipientsDataProvider
     */
    public function testGetRecipients(EmailRecipientsProviderArgs $args, array $resultEmails)
    {
        $this->dqlNameFormatter->expects($this->once())
            ->method('getFormattedNameDQL')
            ->with('u', User::class)
            ->willReturn('u.name');

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($this->createMock(Expr::class));

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('getPrimaryEmailsQb')
            ->willReturn($qb);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($resultEmails);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->willReturn($query);

        $searchQueryMock = $this->createMock(Query::class);

        $searchResultMock = $this->createMock(Item::class);

        $stubResult = new Result($searchQueryMock, [$searchResultMock]);

        $this->indexer->expects($this->once())
            ->method('simpleSearch')
            ->willReturn($stubResult);

        $this->emailRecipientsHelper->getRecipients($args, $userRepository, 'u', User::class);
    }

    public function getRecipientsDataProvider(): array
    {
        return [
            [
                new EmailRecipientsProviderArgs(null, null, 1),
                [
                    [
                        'name' => 'Recipient <recipient@example.com>',
                        'email' => 'recipient@example.com',
                        'entityId' => 1,
                        'organization' => 'org',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider filterRecipientsDataProvider
     */
    public function testFilterRecipients(EmailRecipientsProviderArgs $args, array $recipients, array $expectedResult)
    {
        $this->assertEquals($expectedResult, EmailRecipientsHelper::filterRecipients($args, $recipients));
    }

    public function filterRecipientsDataProvider(): array
    {
        return [
            [
                new EmailRecipientsProviderArgs(null, 're', 100),
                [
                    new Recipient('recipient@example.com', 'Recipient <recipient@example.com>'),
                ],
                [
                    new Recipient('recipient@example.com', 'Recipient <recipient@example.com>'),
                ],
            ],
            [
                new EmailRecipientsProviderArgs(null, 'res', 100),
                [
                    new Recipient('recipient@example.com', 'Recipient <recipient@example.com>'),
                ],
                [],
            ],
            [
                new EmailRecipientsProviderArgs(null, 're', 100, [new Recipient('recipient@example.com', 'name')]),
                [
                    new Recipient('recipient2@example.com', 'Recipient2 <recipient2@example.com>'),
                    new Recipient('recipient@example.com', 'Recipient <recipient@example.com>'),
                ],
                [
                    new Recipient('recipient2@example.com', 'Recipient2 <recipient2@example.com>'),
                ],
            ],
        ];
    }

    /**
     * @dataProvider prepareFormRecipientIdsDataProvider
     */
    public function testPrepareFormRecipientIds(array $ids, string $expectedResult)
    {
        $this->assertEquals($expectedResult, EmailRecipientsHelper::prepareFormRecipientIds($ids));
    }

    public function prepareFormRecipientIdsDataProvider(): array
    {
        return [
            [
                [
                    '"Recipient1 Name; Name2" <recipient1@example.com>',
                    '"Recipient2 Name, Name2" <recipient2@example.com>'
                ],

                base64_encode('"Recipient1 Name; Name2" <recipient1@example.com>') . ';'
                . base64_encode('"Recipient2 Name, Name2" <recipient2@example.com>')
            ]
        ];
    }

    /**
     * @dataProvider extractFormRecipientIdsDataProvider
     */
    public function testExtractFormRecipientIds(string $value, array $expectedResult)
    {
        $this->assertEquals($expectedResult, EmailRecipientsHelper::extractFormRecipientIds($value));
    }

    public function extractFormRecipientIdsDataProvider(): array
    {
        return [
            [
                base64_encode('"Recipient1 Name; Name2" <recipient1@example.com>') . ';'
                . base64_encode('"Recipient2 Name, Name2" <recipient2@example.com>'),
                [
                    '"Recipient1 Name; Name2" <recipient1@example.com>',
                    '"Recipient2 Name, Name2" <recipient2@example.com>'
                ]
            ],
            [
                'recipient1@example.com;recipient2@example.com',
                [
                    'recipient1@example.com',
                    'recipient2@example.com'
                ]
            ]
        ];
    }

    /**
     * @dataProvider recipientsFromResultProvider
     */
    public function testRecipientsFromResult(array $result, string $entityClass, array $expectedRecipients)
    {
        $this->assertEquals(
            $expectedRecipients,
            $this->emailRecipientsHelper->recipientsFromResult($result, $entityClass)
        );
    }

    public function recipientsFromResultProvider(): array
    {
        return [
            [
                [
                    [
                        'name' => 'Recipient',
                        'email' => 'recipient@example.com',
                        'entityId' => 1,
                        'organization' => 'org',
                    ],
                ],
                'Class',
                [
                    'Recipient <recipient@example.com>|Class|org' => new CategorizedRecipient(
                        'recipient@example.com',
                        'Recipient <recipient@example.com>',
                        new RecipientEntity(
                            'Class',
                            1,
                            'Recipient',
                            'org'
                        )
                    )
                ]
            ],
        ];
    }

    /**
     * @dataProvider plainRecipientsFromResultProvider
     */
    public function testPlainRecipientsFromResult(array $result, array $expectedRecipients)
    {
        $this->assertEquals(
            $expectedRecipients,
            $this->emailRecipientsHelper->plainRecipientsFromResult($result)
        );
    }

    public function plainRecipientsFromResultProvider(): array
    {
        return [
            [
                [
                    [
                        'name' => 'Recipient',
                        'email' => 'recipient@example.com',
                        'entityId' => 1,
                        'organization' => 'org',
                    ],
                ],
                [
                    'recipient@example.com' => new CategorizedRecipient(
                        'recipient@example.com',
                        'Recipient <recipient@example.com>'
                    )
                ]
            ],
        ];
    }

    public function testCreateRecipientsFromEmails()
    {
        $emails = [
            'admin@example.com' => '"John Doe" <admin@example.com>',
        ];
        $organization = (new Organization())->setName('ORO');
        $object = new CustomerStub('Customer Name', $organization);
        $objectClass = get_class($object);

        $em = $this->createMock(ObjectManager::class);
        $objectMetadata = new ClassMetadata(CustomerStub::class);
        $objectMetadata->identifier = ['id'];
        $objectMetadata->reflFields = [
            'id' => new ReflectionProperty(CustomerStub::class, 'id', [spl_object_hash($object) => 1])
        ];

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with($objectClass)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($objectClass)
            ->willReturn($objectMetadata);

        $this->nameFormatter->expects(self::once())
            ->method('format')
            ->with($object)
            ->willReturn('Customer Name');

        $this->assertGetClassLabelCalls(CustomerStub::class, 'Customer Stub');

        $expected = [
            '"John Doe" <admin@example.com>|Oro\Bundle\EmailBundle\Tests\Unit\Stub\CustomerStub|ORO' => new Recipient(
                'admin@example.com',
                '"John Doe" <admin@example.com>',
                new RecipientEntity(
                    CustomerStub::class,
                    1,
                    'Customer Name (Customer Stub)',
                    'ORO'
                )
            ),
        ];

        $this->assertEquals(
            $expected,
            $this->emailRecipientsHelper->createRecipientsFromEmails($emails, $object)
        );
    }

    public function testCreateRecipientsFromEmailsNoRecipientEntity()
    {
        $emails = [
            'admin@example.com' => '"John Doe" <admin@example.com>',
        ];
        $object = new \stdClass();
        $objectClass = get_class($object);

        $em = $this->createMock(ObjectManager::class);
        $objectMetadata = $this->createMock(ClassMetadata::class);
        $objectMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with(self::identicalTo($object))
            ->willReturn([]);

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with($objectClass)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($objectClass)
            ->willReturn($objectMetadata);

        $expected = [
            'admin@example.com' => new Recipient(
                'admin@example.com',
                '"John Doe" <admin@example.com>',
                null
            ),
        ];

        $this->assertEquals(
            $expected,
            $this->emailRecipientsHelper->createRecipientsFromEmails($emails, $object)
        );
    }

    public function testCreateRecipientsFromEmailsEmptyEmails()
    {
        $emails = [];
        $object = new \stdClass();
        $objectClass = get_class($object);

        $em = $this->createMock(ObjectManager::class);
        $objectMetadata = $this->createMock(ClassMetadata::class);
        $objectMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with(self::identicalTo($object))
            ->willReturn([]);

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with($objectClass)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($objectClass)
            ->willReturn($objectMetadata);

        $expected = [];

        $this->assertEquals(
            $expected,
            $this->emailRecipientsHelper->createRecipientsFromEmails($emails, $object)
        );
    }

    /**
     * @dataProvider creteRecipientEntityDataProvider
     */
    public function testCreateRecipientEntity(object $object, RecipientEntity $recipientEntity)
    {
        $className = get_class($object);
        $objectMetadata = $this->getEntity(ClassMetadata::class, [
            'name' => $className,
            'identifier' => ['id'],
            'reflFields' => [
                'id' => new ReflectionProperty($className, 'id', [spl_object_hash($object) => 1])
            ],
        ], [null]);
        $this->nameFormatter->expects(self::once())
            ->method('format')
            ->with($object)
            ->willReturn('NAME');

        $this->assertGetClassLabelCalls($className, 'Object Stub');

        $this->assertEquals(
            $recipientEntity,
            $this->emailRecipientsHelper->createRecipientEntity($object, $objectMetadata)
        );
    }

    public function creteRecipientEntityDataProvider(): array
    {
        return [
            [
                new CustomerStub('Customer Name', (new Organization())->setName('ORO')),
                new RecipientEntity(CustomerStub::class, 1, 'NAME (Object Stub)', 'ORO')
            ],
            [
                new AddressStub('Address Name', 'OrgName'),
                new RecipientEntity(AddressStub::class, 1, 'NAME (Object Stub)', null)
            ]
        ];
    }

    protected function assertGetClassLabelCalls(string $className, string $label): void
    {
        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);

        $config = $this->createMock(ConfigInterface::class);

        $this->configManager->expects(self::once())
            ->method('getConfig')
            ->with(new EntityConfigId('entity', $className))
            ->willReturn($config);

        $config->expects(self::once())
            ->method('get')
            ->with('label')
            ->willReturn('ObjectStub');

        $this->translator->expects(self::once())
            ->method('trans')
            ->with('ObjectStub')
            ->willReturn($label);
    }
}
