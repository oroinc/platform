<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Model\CategorizedRecipient;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Model\RecipientEntity;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Tests\Unit\Stub\CustomerStub;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ReflectionProperty;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmailRecipientsHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $aclHelper;

    /** @var DQLNameFormatter|\PHPUnit\Framework\MockObject\MockObject */
    protected $dqlNameFormatter;

    /** @var NameFormatter|\PHPUnit\Framework\MockObject\MockObject */
    protected $nameFormatter;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var EmailOwnerProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $emailOwnerProvider;

    /** @var Registry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var EmailAddressHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $addressHelper;

    /** @var EmailRecipientsHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $emailRecipientsHelper;

    /** @var Indexer|\PHPUnit\Framework\MockObject\MockObject */
    protected $indexer;

    protected function setUp(): void
    {
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->dqlNameFormatter = $this->createMock(DQLNameFormatter::class);
        $this->nameFormatter = $this->createMock(NameFormatter::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->emailOwnerProvider = $this->createMock(EmailOwnerProvider::class);
        $this->registry = $this->createMock(Registry::class);
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
            $this->indexer
        );
    }

    /**
     * @dataProvider getRecipientsDataProvider
     */
    public function testGetRecipients(EmailRecipientsProviderArgs $args, array $resultEmails)
    {
        $this->dqlNameFormatter->expects($this->once())
            ->method('getFormattedNameDQL')
            ->with('u', 'Oro\Bundle\UserBundle\Entity\User')
            ->will($this->returnValue('u.name'));

        $expressionBuiled = $this->getMockBuilder('Doctrine\ORM\Query\Expr')
            ->disableOriginalConstructor()
            ->setMethods(['in'])
            ->getMock();

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('setMaxResults')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('andWhere')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('expr')
            ->will($this->returnValue($expressionBuiled));

        $userRepository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $userRepository->expects($this->once())
            ->method('getPrimaryEmailsQb')
            ->will($this->returnValue($qb));

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractclass();
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($resultEmails));

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->will($this->returnValue($query));

        $searchQueryMock = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Query')
            ->disableOriginalConstructor()
            ->getMock();

        $searchResultMock = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result\Item')
            ->disableOriginalConstructor()
            ->getMock();

        $stubResult = new Result($searchQueryMock, [$searchResultMock]);

        $this->indexer->expects($this->once())
             ->method('simpleSearch')
             ->will($this->returnValue($stubResult));

        $this->emailRecipientsHelper->getRecipients($args, $userRepository, 'u', 'Oro\Bundle\UserBundle\Entity\User');
    }

    /**
     * @return array
     */
    public function getRecipientsDataProvider()
    {
        return [
            [
                new EmailRecipientsProviderArgs(null, null, 1),
                [
                    [
                        'name'  => 'Recipient <recipient@example.com>',
                        'email' => 'recipient@example.com',
                        'entityId'     => 1,
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

    /**
     * @return array
     */
    public function filterRecipientsDataProvider()
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
    public function testPrepareFormRecipientIds($ids, $expectedResult)
    {
        $this->assertEquals($expectedResult, EmailRecipientsHelper::prepareFormRecipientIds($ids));
    }

    /**
     * @return array
     */
    public function prepareFormRecipientIdsDataProvider()
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
    public function testExtractFormRecipientIds($value, $expectedResult)
    {
        $this->assertEquals($expectedResult, EmailRecipientsHelper::extractFormRecipientIds($value));
    }

    /**
     * @return array
     */
    public function extractFormRecipientIdsDataProvider()
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
    public function testRecipientsFromResult(array $result, $entityClass, array $expectedRecipients)
    {
        $this->assertEquals(
            $expectedRecipients,
            $this->emailRecipientsHelper->recipientsFromResult($result, $entityClass)
        );
    }

    /**
     * @return array
     */
    public function recipientsFromResultProvider()
    {
        return [
            [
                [
                    [
                        'name'  => 'Recipient',
                        'email' => 'recipient@example.com',
                        'entityId'     => 1,
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
    public function testRlainRecipientsFromResult(array $result, array $expectedRecipients)
    {
        $this->assertEquals(
            $expectedRecipients,
            $this->emailRecipientsHelper->plainRecipientsFromResult($result)
        );
    }

    /**
     * @return array
     */
    public function plainRecipientsFromResultProvider()
    {
        return [
            [
                [
                    [
                        'name'  => 'Recipient',
                        'email' => 'recipient@example.com',
                        'entityId'     => 1,
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
        $objectMetadata = $this->getEntity(ClassMetadata::class, [
            'name' => CustomerStub::class,
            'identifier' => ['id'],
            'reflFields' => [
                'id' => new ReflectionProperty(CustomerStub::class, 'id', [spl_object_hash($object) => 1])
            ],
        ], [null]);

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

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with(CustomerStub::class)
            ->willReturn(true);

        $config = $this->createMock(ConfigInterface::class);

        $this->configManager->expects(self::once())
            ->method('getConfig')
            ->with(new EntityConfigId('entity', CustomerStub::class))
            ->willReturn($config);

        $config->expects(self::once())
            ->method('get')
            ->with('label')
            ->willReturn('CustomerStub');

        $this->translator->expects(self::once())
            ->method('trans')
            ->with('CustomerStub')
            ->willReturn('Customer Stub');

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
}
