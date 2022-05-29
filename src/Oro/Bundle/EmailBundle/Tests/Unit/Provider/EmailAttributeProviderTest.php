<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Model\EmailAttribute;
use Oro\Bundle\EmailBundle\Provider\EmailAttributeProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailHolder;
use Oro\Bundle\EmailBundle\Tests\Unit\Stub\CustomerStub;
use Oro\Bundle\EmailBundle\Tests\Unit\Stub\OrderStub;
use Oro\Bundle\EmailBundle\Tests\Unit\Stub\UserStub;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;

class EmailAttributeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var NameFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $nameFormatter;

    /** @var EmailAddressHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAddressHelper;

    /** @var EmailAttributeProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->nameFormatter = $this->createMock(NameFormatter::class);
        $this->emailAddressHelper = $this->createMock(EmailAddressHelper::class);

        $this->provider = new EmailAttributeProvider(
            $this->registry,
            $this->configManager,
            $this->nameFormatter,
            $this->emailAddressHelper
        );
    }

    /**
     * @dataProvider getAttributesDataProvider
     */
    public function testGetAttributes(string $className, ClassMetadata $metadata, array $expected)
    {
        $em = $this->createMock(ObjectManager::class);

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with($className)
            ->willReturn($em);

        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($metadata);

        $this->expectConfigManager($metadata);

        $this->assertEquals(
            $expected,
            $this->provider->getAttributes($className)
        );
    }

    public function getAttributesDataProvider(): array
    {
        return [
            'without EmailHolderInterface' => [
                'className' => CustomerStub::class,
                'metadata' => $this->getClassMetadata(
                    CustomerStub::class,
                    [
                        'name' => 'name',
                        'email' => 'email',
                        'hidden_email' => 'hiddenEmail',
                        'secondary_email' => 'secondaryEmail',
                        'no_config' => 'noConfig',
                        'deleted_email' => 'deletedEmail',
                        'has_contact_information' => 'hasContactInformation',
                    ]
                ),
                'expected' => [
                    new EmailAttribute('email'),
                    new EmailAttribute('secondaryEmail'),
                    new EmailAttribute('hasContactInformation'),
                ]
            ],
            'with EmailHolderInterface' => [
                'className' => TestEmailHolder::class,
                'metadata' => $this->getClassMetadata(
                    CustomerStub::class,
                    [
                        'name' => 'name',
                        'email' => 'email',
                        'hidden_email' => 'hiddenEmail',
                        'secondary_email' => 'secondaryEmail',
                        'no_config' => 'noConfig',
                        'deleted_email' => 'deletedEmail',
                        'has_contact_information' => 'hasContactInformation',
                    ]
                ),
                'expected' => [
                    new EmailAttribute('email'),
                    new EmailAttribute('email'),
                    new EmailAttribute('secondaryEmail'),
                    new EmailAttribute('hasContactInformation'),
                ]
            ],
        ];
    }

    private function getClassMetadata(string $entityName, array $fieldNames): ClassMetadata
    {
        $classMetadata = new ClassMetadata($entityName);
        $classMetadata->fieldNames = $fieldNames;

        return $classMetadata;
    }

    private function expectConfigManager(ClassMetadata $metadata)
    {
        $this->configManager->expects(self::any())
            ->method('hasConfig')
            ->with($metadata->name, self::isType('string'))
            ->willReturnCallback(function ($className, $fieldName) {
                return 'noConfig' !== $fieldName;
            });

        $this->configManager->expects(self::any())
            ->method('isHiddenModel')
            ->with($metadata->name, self::isType('string'))
            ->willReturnCallback(function ($className, $fieldName) {
                return 'hiddenEmail' === $fieldName;
            });

        $this->configManager->expects(self::any())
            ->method('getFieldConfig')
            ->with(self::isType('string'), $metadata->name, self::isType('string'))
            ->willReturnCallback(function ($scope, $className, $fieldName) {
                switch ($scope) {
                    case 'extend':
                        $extendFieldConfig = $this->createMock(ConfigInterface::class);

                        $extendFieldConfig->expects(self::any())
                            ->method('is')
                            ->with('is_deleted')
                            ->willReturnCallback(function () use ($fieldName) {
                                return $fieldName === 'deletedEmail';
                            });

                        return $extendFieldConfig;
                    case 'entity':
                        $entityFieldConfig = $this->createMock(ConfigInterface::class);

                        $entityFieldConfig->expects(self::any())
                            ->method('get')
                            ->with('contact_information')
                            ->willReturnCallback(function () use ($fieldName) {
                                return $fieldName === 'hasContactInformation' ? 'email' : null;
                            });

                        return $entityFieldConfig;
                    default:
                        return null;
                }
            });
    }

    public function testCreateEmailsFromAttributes()
    {
        $user = new UserStub(1, 'admin@example.com');
        $customer = new CustomerStub();

        $object = new OrderStub(
            $user,
            $customer,
            'order@example.com'
        );

        $attributes = [
            new EmailAttribute('email'),
            new EmailAttribute('secondaryEmail'),
            new EmailAttribute('user'),
            new EmailAttribute('customer'),
        ];

        $this->nameFormatter->expects(self::exactly(2))
            ->method('format')
            ->with(self::isType('object'))
            ->willReturnCallback(function ($owner) {
                if (is_a($owner, UserStub::class)) {
                    return 'John Doe';
                }

                if (is_a($owner, OrderStub::class)) {
                    return 'Order Number';
                }

                return '';
            });

        $this->emailAddressHelper->expects(self::exactly(2))
            ->method('buildFullEmailAddress')
            ->with(self::isType('string'), self::isType('string'))
            ->willReturnCallback(function ($email, $ownerName) {
                if ($ownerName) {
                    return sprintf('"%s" <%s>', $ownerName, $email);
                }

                return $email;
            });

        $expected = [
            'order@example.com' => '"Order Number" <order@example.com>',
            'admin@example.com' => '"John Doe" <admin@example.com>',
        ];

        $this->assertEquals(
            $expected,
            $this->provider->createEmailsFromAttributes($attributes, $object)
        );
    }
}
