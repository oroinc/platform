<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\Inflector\Rules\English\InflectorFactory;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Async\AbstractAuditProcessor;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Entity\Repository\AuditRepository;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Async\AuditChangedEntitiesExtensionTrait;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MessageQueueBundle\Test\Functional\JobsAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Config\Common\ConfigObject;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD)
 */
class DataAuditTest extends WebTestCase
{
    use MessageQueueAssertTrait;
    use AuditChangedEntitiesExtensionTrait;
    use JobsAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $token = new UsernamePasswordOrganizationToken(
            $this->findAdmin(),
            self::AUTH_PW,
            'main',
            $this->findAdmin()->getOrganization(),
            $this->findAdmin()->getUserRoles()
        );
        self::getContainer()->get('security.token_storage')->setToken($token);

        $this->getOptionalListenerManager()->enableListener(
            'oro_dataaudit.listener.send_changed_entities_to_message_queue'
        );
    }

    protected function tearDown(): void
    {
        $doctrine = self::getContainer()->get('doctrine');
        $entityManager = $doctrine->getManager();
        /** @var AuditRepository $repository */
        $repository = $doctrine->getRepository(Audit::class);
        foreach ($repository->findAll() as $entity) {
            $entityManager->remove($entity);
        }
        $entityManager->flush();
    }

    public function testCoverage()
    {
        $typesToTest = [];
        $doctrineTypes = Type::getTypesMap();
        foreach ($doctrineTypes as $doctrineType => $doctrineTypeClass) {
            $typesToTest[$doctrineType] = true;
        }

        foreach (RelationType::$anyToAnyRelations as $doctrineType) {
            $typesToTest[$doctrineType] = true;
        }

        foreach (RelationType::$toAnyRelations as $doctrineType) {
            $typesToTest[$doctrineType] = true;
        }

        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = self::getContainer()->get('oro_entity.doctrine_helper');

        /** @var FieldConfigModel[] $fields */
        $fields = $doctrineHelper
            ->getEntityManager(FieldConfigModel::class)
            ->getRepository(FieldConfigModel::class)
            ->findAll();

        foreach ($fields as $field) {
            $typesToTest[$field->getType()] = true;
        }

        // ascii_string is supported only for the SQL Server, so we should not test it. See:
        // https://github.com/doctrine/dbal/blob/faf8ddd7e09e495d890a7579f842e5b6fc24aa4a/docs/en/reference/types.rst
        unset($typesToTest['ascii_string']);

        $missingMethods = [];
        $typesToTest = array_keys($typesToTest);
        sort($typesToTest);
        foreach ($typesToTest as $type) {
            $methodName = 'test'.ucfirst((new InflectorFactory())->build()->camelize(\ucwords($type, '_-')));
            if (method_exists($this, $methodName)) {
                continue;
            }

            $missingMethods[] = $methodName;
        }

        self::assertEmpty($missingMethods, sprintf('Tests are missing: %s', implode(', ', $missingMethods)));
    }

    public function testArray()
    {
        $owner = new TestAuditDataOwner();
        $owner->setArrayProperty([1, 2]);

        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setArrayProperty([1, 3]);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>{&quot;0&quot;:1,&quot;1&quot;:2}</s>' .
                        '&nbsp;{&quot;0&quot;:1,&quot;1&quot;:3}',
                ],
            ]
        );
    }

    public function testBigint()
    {
        $owner = new TestAuditDataOwner();
        $owner->setBigintProperty(1);

        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setBigintProperty(2);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>1</s>&nbsp;2',
                ],
            ]
        );
    }

    public function testBinary()
    {
        $owner = new TestAuditDataOwner();

        $em = $this->saveOwnerAndClearMessages($owner);

        $value = base_convert(unpack('H*', 'Test')[1], 16, 2);
        $owner->setBinaryProperty($value);
        $em->flush();

        $this->processMessages();
        $this->assertStoredAuditCount(0);

        $owner = $em->find(TestAuditDataOwner::class, $owner->getId());

        $storedValue = $owner->getBinaryProperty();
        self::assertEquals('Test', pack('H*', base_convert($storedValue, 2, 16)));
    }

    public function testBlob()
    {
        $owner = new TestAuditDataOwner();

        $em = $this->saveOwnerAndClearMessages($owner);

        $resource = fopen(__FILE__, 'rb');
        if (false === $resource) {
            $this->fail('Unable to open resource');
        }
        $owner->setBlobProperty($resource);
        $em->flush();

        $this->processMessages();
        $this->assertStoredAuditCount(0);

        $owner = $em->find(TestAuditDataOwner::class, $owner->getId());
        self::assertIsResource($owner->getBlobProperty());
    }

    public function testBoolean()
    {
        $owner = new TestAuditDataOwner();
        $owner->setBooleanProperty(false);

        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setBooleanProperty(true);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>No</s>&nbsp;Yes',
                ],
            ]
        );
    }

    public function testConfigObject()
    {
        $owner = new TestAuditDataOwner();

        $em = $this->saveOwnerAndClearMessages($owner);

        $configObjectProperty = ConfigObject::create(['val' => 1]);
        $owner->setConfigObjectProperty($configObjectProperty);
        $em->flush();

        $this->processMessages();
        $this->assertStoredAuditCount(0);
    }

    public function testCryptedString()
    {
        $owner = new TestAuditDataOwner();
        $owner->setCryptedStringProperty('str');

        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setCryptedStringProperty('str2');
        $em->flush();

        $this->processMessages();
        $this->assertStoredAuditCount(0);
    }

    public function testCurrency()
    {
        $owner = new TestAuditDataOwner();
        $owner->setCurrencyProperty('USD');

        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setCurrencyProperty('EUR');
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>USD</s>&nbsp;EUR',
                ],
            ]
        );
    }

    public function testDate()
    {
        $owner = new TestAuditDataOwner();
        $owner->setDateProperty(new \DateTime('2019-01-01 00:00:00', new \DateTimeZone('UTC')));
        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setDateProperty(new \DateTime('2019-01-02 00:00:00', new \DateTimeZone('UTC')));
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>Jan 1, 2019</s>&nbsp;Jan 2, 2019',
                ],
            ]
        );
    }

    public function testDatetime()
    {
        $owner = new TestAuditDataOwner();
        $owner->setDateTimeProperty(new \DateTime('2019-01-01 00:00:00', new \DateTimeZone('UTC')));
        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setDateTimeProperty(new \DateTime('2019-01-02 00:00:00', new \DateTimeZone('UTC')));
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>Jan 1, 2019, 12:00 AM</s>&nbsp;Jan 2, 2019, 12:00 AM',
                ],
            ]
        );
    }

    public function testDatetimetz()
    {
        $owner = new TestAuditDataOwner();
        $owner->setDateTimeTzProperty(new \DateTime('2019-01-01 00:00:00', new \DateTimeZone('UTC')));
        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setDateTimeTzProperty(new \DateTime('2019-01-02 00:00:00', new \DateTimeZone('UTC')));
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>Jan 1, 2019, 12:00 AM</s>&nbsp;Jan 2, 2019, 12:00 AM',
                ],
            ]
        );
    }

    public function testDecimal()
    {
        $owner = new TestAuditDataOwner();
        $owner->setDecimalProperty(0.001);
        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setDecimalProperty(0.002);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>0.001</s>&nbsp;0.002',
                ],
            ]
        );
    }

    public function testDuration()
    {
        $owner = new TestAuditDataOwner();
        $owner->setDurationProperty(2);
        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setDurationProperty(3);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>2</s>&nbsp;3',
                ],
            ]
        );
    }

    public function testFile()
    {
        $this->markTestSkipped('File audit not supported');
    }

    public function testFloat()
    {
        $owner = new TestAuditDataOwner();
        $owner->setFloatProperty(0.001);
        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setFloatProperty(0.002);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>0.001</s>&nbsp;0.002',
                ],
            ]
        );
    }

    public function testGuid()
    {
        $owner = new TestAuditDataOwner();
        $owner->setGuidProperty('ca205501-a584-4e16-bb19-0226cbb9e1c8');

        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setGuidProperty('731d9a5c-1a42-4bb0-92f3-13f8c7824536');
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() =>
                        '<s>ca205501-a584-4e16-bb19-0226cbb9e1c8</s>&nbsp;731d9a5c-1a42-4bb0-92f3-13f8c7824536',
                ],
            ]
        );
    }

    public function testImage()
    {
        $this->markTestSkipped('Image audit not supported');
    }

    public function testInteger()
    {
        $owner = new TestAuditDataOwner();
        $owner->setIntegerProperty(2);
        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setIntegerProperty(3);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>2</s>&nbsp;3',
                ],
            ]
        );
    }

    public function testJsonArray()
    {
        $owner = new TestAuditDataOwner();
        $owner->setArrayProperty([1, 2]);

        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setArrayProperty([1, 3]);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>{&quot;0&quot;:1,&quot;1&quot;:2}</s>' .
                        '&nbsp;{&quot;0&quot;:1,&quot;1&quot;:3}',
                ],
            ]
        );
    }

    public function testManyToMany()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenManyToMany($child);
        $child->setOwners(new ArrayCollection([$owner]));

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->processMessages(true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; added', $child->getId()),
                        '<s></s>childString',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => [
                        sprintf('&quot;%s&quot; added', $owner->getId()),
                        '<s></s>ownerString',
                    ],
                ],
            ]
        );
    }

    public function testManyToManyRemove()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');

        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');
        $child->setOwners(new ArrayCollection([$owner]));

        $owner->addChildrenManyToMany($child);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $owner->removeChildrenManyToMany($child);
        $em->flush();

        $this->processMessages(true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => sprintf('Item #%s&quot; removed', $child->getId()),
                ],
                TestAuditDataChild::class => [
                    $child->getId() => sprintf('&quot;%s&quot; removed', $owner->getId()),
                ],
            ]
        );
    }

    public function testManyToManyRemoveOwner()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenManyToMany($child);
        $child->setOwners(new ArrayCollection([$owner]));

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setOwners(new ArrayCollection([]));
        $em->flush();

        $this->processMessages(true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => sprintf('Item #%s&quot; removed', $child->getId()),
                ],
                TestAuditDataChild::class => [
                    $child->getId() => sprintf('&quot;%s&quot; removed', $owner->getId()),
                ],
            ]
        );
    }

    public function testManyToManyUpdate()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenManyToMany($child);
        $child->setOwners(new ArrayCollection([$owner]));

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setStringProperty('childString2');
        $em->flush();

        $this->processMessages(false, true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; changed', $child->getId()),
                        '<s>childString</s>&nbsp;childString2',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => '<s>childString</s>&nbsp;childString2',
                ],
            ]
        );
    }

    public function testManyToManyUpdateNonReverseOwner()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenManyToMany($child);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setStringProperty('childString2');
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataChild::class => [
                    $child->getId() => '<s>childString</s>&nbsp;childString2',
                ],
            ]
        );
    }

    public function testManyToManyUpdateNonReverseChild()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $child->setOwners(new ArrayCollection([$owner]));

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setStringProperty('childString2');
        $em->flush();

        $this->processMessages(false, true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; changed', $child->getId()),
                        '<s>childString</s>&nbsp;childString2',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => '<s>childString</s>&nbsp;childString2',
                ],
            ]
        );
    }

    public function testManyToManyUpdateOwner()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenManyToMany($child);
        $child->setOwners(new ArrayCollection([$owner]));

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $owner->setStringProperty('ownerString2');
        $em->flush();

        $this->processMessages(false, true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>ownerString</s>&nbsp;ownerString2',
                ],
                TestAuditDataChild::class => [
                    $child->getId() => [
                        sprintf('&quot;%s&quot; changed', $owner->getId()),
                        '<s>ownerString</s>&nbsp;ownerString2',
                    ],
                ],
            ]
        );
    }

    public function testManyToManyUnidirectional()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenManyToManyUnidirectional($child);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->processMessages(true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; added', $child->getId()),
                        '<s></s>childString',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => [
                        '<s></s>&nbsp;childString',
                    ],
                ],
            ]
        );
    }

    public function testManyToManyRemoveUnidirectional()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenManyToManyUnidirectional($child);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $owner->removeChildrenManyToManyUnidirectional($child);
        $em->flush();

        $this->processMessages(true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => sprintf('Item #%s&quot; removed', $child->getId()),
                ],
            ]
        );
    }

    public function testManyToManyUpdateUnidirectional()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenManyToManyUnidirectional($child);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setStringProperty('childString2');
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataChild::class => [
                    $child->getId() => '<s>childString</s>&nbsp;childString2',
                ],
            ]
        );
    }

    public function testManyToManyUpdateOwnerUnidirectional()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenManyToManyUnidirectional($child);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $owner->setStringProperty('ownerString2');
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>ownerString</s>&nbsp;ownerString2',
                ],
            ]
        );
    }

    public function testMultiEnum()
    {
        $owner = new TestAuditDataOwner();

        $className = ExtendHelper::buildEnumValueClassName('audit_muenum');

        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $this->getEntityManager()->getRepository($className);

        $enum1 = $enumRepo->createEnumValue('enum1', 1, true);
        $enum2 = $enumRepo->createEnumValue('enum2', 2, false);
        $enum34 = $enumRepo->createEnumValue('enum3', 3, false);
        $owner->addMultiEnumProperty($enum1);
        $owner->addMultiEnumProperty($enum2);
        $owner->addMultiEnumProperty($enum34);
        $em = $this->getEntityManager();
        $em->persist($enum1);
        $em->persist($enum2);
        $em->persist($enum34);
        $em->persist($owner);
        $em->flush();
        $this->getMessageCollector()->clear();

        $enum5 = $enumRepo->createEnumValue('enum5', 5, false);
        $enum6 = $enumRepo->createEnumValue('enum6', 6, false);
        $enum34->setName('enum4');
        $owner->removeMultiEnumProperty($enum1);
        $owner->removeMultiEnumProperty($enum2);
        $owner->addMultiEnumProperty($enum5);
        $owner->addMultiEnumProperty($enum6);
        $em->remove($enum1);
        $em->persist($enum34);
        $em->persist($enum5);
        $em->persist($enum6);
        $em->flush();

        $this->processMessages(true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        '&quot;enum5&quot; added',
                        '<s></s>enum5',
                        '&quot;enum6&quot; added',
                        '<s></s>enum6',
                        '&quot;enum1&quot; removed',
                        '<s>enum1</s>',
                        '&quot;enum2&quot; removed',
                        '&quot;enum4&quot; changed',
                        '<s>enum3</s>&nbsp;enum4',
                    ],
                ],
                'Extend\Entity\EV_Audit_Muenum' => [
                    'enum1' => '<s>enum1</s>',
                    'enum3' => '<s>enum3</s>&nbsp;enum4',
                    'enum5' => '<s></s>&nbsp;enum5',
                    'enum6' => '<s></s>&nbsp;enum6',
                ],
            ]
        );
    }

    public function testMultiEnumRemove()
    {
        $owner = new TestAuditDataOwner();

        $className = ExtendHelper::buildEnumValueClassName('audit_muenum');

        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $this->getEntityManager()->getRepository($className);

        $enum1 = $enumRepo->createEnumValue('enum1', 1, true);
        $enum2 = $enumRepo->createEnumValue('enum2', 2, false);
        $enum3 = $enumRepo->createEnumValue('enum3', 3, false);
        $owner->addMultiEnumProperty($enum1);
        $owner->addMultiEnumProperty($enum2);
        $owner->addMultiEnumProperty($enum3);
        $em = $this->getEntityManager();
        $em->persist($enum1);
        $em->persist($enum2);
        $em->persist($enum3);
        $em->persist($owner);
        $em->flush();
        $this->getMessageCollector()->clear();

        $owner->removeMultiEnumProperty($enum1);
        $owner->removeMultiEnumProperty($enum2);
        $owner->removeMultiEnumProperty($enum3);
        $em->flush();

        $this->processMessages(true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        '&quot;enum1&quot; removed',
                        '&quot;enum2&quot; removed',
                        '&quot;enum3&quot; removed',
                    ],
                ],
            ]
        );
    }

    public function testMultiEnumClear(): void
    {
        $owner = new TestAuditDataOwner();
        $className = ExtendHelper::buildEnumValueClassName('audit_muenum');

        /** @var EnumValueRepository $enumRepository */
        $enumRepository = $this->getEntityManager()->getRepository($className);

        $enum1 = $enumRepository->createEnumValue('enum1', 1, true);
        $enum2 = $enumRepository->createEnumValue('enum2', 2, false);
        $enumAfterCollectionClear = $enumRepository->createEnumValue('enum3', 3, false);
        $owner->addMultiEnumProperty($enum1);
        $owner->addMultiEnumProperty($enum2);

        $em = $this->getEntityManager();
        $em->persist($enum1);
        $em->persist($enum2);
        $em->persist($enumAfterCollectionClear);
        $em->persist($owner);
        $em->flush();

        $this->getMessageCollector()->clear();
        $owner->getMultiEnumProperty()->clear();
        $owner->addMultiEnumProperty($enumAfterCollectionClear);

        $em->flush();
        $this->processMessages(true);
        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        '&quot;enum1&quot; removed',
                        '&quot;enum2&quot; removed',
                        '&quot;enum3&quot; added',
                    ],
                ],
            ]
        );
    }

    public function testManyToManyClear(): void
    {
        $owner = new TestAuditDataOwner();
        $child1 = new TestAuditDataChild();
        $child2 = new TestAuditDataChild();
        $child3 = new TestAuditDataChild();

        $owner->addChildrenManyToMany($child1);
        $owner->addChildrenManyToMany($child2);

        $em = $this->getEntityManager();
        $em->persist($child1);
        $em->persist($child2);
        $em->persist($child3);
        $em->persist($owner);
        $em->flush();

        $this->getMessageCollector()->clear();

        $owner->getChildrenManyToMany()->clear();
        $owner->addChildrenManyToMany($child3);

        $em->flush();
        $this->processMessages(true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; added', $child3->getId()),
                        sprintf('Item #%s&quot; removed', $child1->getId()),
                        sprintf('Item #%s&quot; removed', $child2->getId()),
                    ],
                ],
                TestAuditDataChild::class => [
                    $child1->getId() => [sprintf('&quot;%s&quot; removed', $owner->getId())],
                    $child2->getId() => [sprintf('&quot;%s&quot; removed', $owner->getId())],
                    $child3->getId() => [sprintf('&quot;%s&quot; added', $owner->getId())]
                ]
            ]
        );
    }

    public function testManyToOne()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setOwnerManyToOne($owner);
        $owner->addChildrenOneToMany($child);
        $child->setStringProperty('childString2');
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; added', $child->getId()),
                        '<s>childString</s>&nbsp;childString2',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => [
                        sprintf('&quot;%s&quot; added', $owner->getId()),
                        '<s>childString</s>&nbsp;childString2',
                    ],
                ],
            ]
        );
    }

    public function testManyToOneUpdate()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');
        $child->setOwnerManyToOne($owner);
        $owner->addChildrenOneToMany($child);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setStringProperty('childString2');
        $em->flush();

        $this->processMessages(false, true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; changed', $child->getId()),
                        '<s>childString</s>&nbsp;childString2',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => [
                        '<s>childString</s>&nbsp;childString2',
                    ],
                ],
            ]
        );
    }

    public function testManyToOneRemove()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');
        $child->setOwnerManyToOne($owner);
        $owner->addChildrenOneToMany($child);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $owner->removeChildrenOneToMany($child);
        $child->setOwnerManyToOne(null);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; removed', $child->getId()),
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => [
                        sprintf('&quot;%s&quot; removed', $owner->getId()),
                    ],
                ],
            ]
        );
    }

    /**
     * @see \Oro\Bundle\DataAuditBundle\Tests\Functional\DataAuditTest::testOneToMany
     * @see \Oro\Bundle\DataAuditBundle\Tests\Functional\DataAuditTest::testManyToMany
     */
    public function testRefMany()
    {
        $this->markTestSkipped('Check testOneToMany and testManyToMany');
    }

    /**
     * @see \Oro\Bundle\DataAuditBundle\Tests\Functional\DataAuditTest::testManyToOne
     * @see \Oro\Bundle\DataAuditBundle\Tests\Functional\DataAuditTest::testOneToOne
     */
    public function testRefOne()
    {
        $this->markTestSkipped('Check testManyToOne and testOneToOne');
    }

    public function testEnum()
    {
        $owner = new TestAuditDataOwner();

        $className = ExtendHelper::buildEnumValueClassName('audit_enum');

        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $this->getEntityManager()->getRepository($className);

        $enum1 = $enumRepo->createEnumValue('enum1', 1, true);
        $owner->setEnumProperty($enum1);
        $em = $this->getEntityManager();
        $em->persist($enum1);
        $em->persist($owner);
        $em->flush();
        $this->getMessageCollector()->clear();

        $enum2 = $enumRepo->createEnumValue('enum2', 2, false);
        $owner->setEnumProperty($enum2);
        $em->persist($enum2);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        '&quot;enum2&quot; added',
                        '&quot;enum1&quot; removed',
                    ],
                ],
                'Extend\Entity\EV_Audit_Enum' => [
                    'enum2' => '<s></s>&nbsp;enum2',
                ],
            ]
        );
    }

    public function testMoney()
    {
        $owner = new TestAuditDataOwner();
        $owner->setMoneyProperty(1.01);

        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setMoneyProperty(1.02);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>1.01</s>&nbsp;1.02',
                ],
            ]
        );
    }

    public function testMoneyValue()
    {
        $owner = new TestAuditDataOwner();
        $owner->setMoneyValueProperty('1.01');

        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setMoneyValueProperty('1.02');
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>1.01</s>&nbsp;1.02',
                ],
            ]
        );
    }

    public function testObject()
    {
        $owner = new TestAuditDataOwner();
        $owner->setObjectProperty(new \stdClass());

        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setObjectProperty(new \stdClass());
        $em->flush();

        $this->processMessages();
        $this->assertStoredAuditCount(0);
    }

    public function testOneToMany()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenOneToMany($child);
        $child->setOwnerManyToOne($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; added', $child->getId()),
                        '<s></s>childString',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => [
                        sprintf('&quot;%s&quot; added', $owner->getId()),
                        '<s></s>&nbsp;childString',
                    ],
                ],
            ]
        );
    }

    public function testOneToManyRemove()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenOneToMany($child);
        $child->setOwnerManyToOne($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $owner->removeChildrenOneToMany($child);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => sprintf('Item #%s&quot; removed', $child->getId()),
                ],
                TestAuditDataChild::class => [
                    $child->getId() => sprintf('&quot;%s&quot; removed', $owner->getId()),
                ],
            ]
        );
    }

    public function testOneToManyRemoveOwner()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenOneToMany($child);
        $child->setOwnerManyToOne($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setOwnerManyToOne(null);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => sprintf('Item #%s&quot; removed', $child->getId()),
                ],
                TestAuditDataChild::class => [
                    $child->getId() => sprintf('&quot;%s&quot; removed', $owner->getId()),
                ],
            ]
        );
    }

    public function testOneToManyUpdate()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenOneToMany($child);
        $child->setOwnerManyToOne($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setStringProperty('childString2');
        $em->flush();

        $this->processMessages(false, true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; changed', $child->getId()),
                        '<s>childString</s>&nbsp;childString2',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => '<s>childString</s>&nbsp;childString2',
                ],
            ]
        );
    }

    public function testOneToManyUpdateNonReverseOwner()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenOneToMany($child);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setStringProperty('childString2');
        $em->flush();

        $this->processMessages(false, true);

        $this->assertData(
            [
                TestAuditDataChild::class => [
                    $child->getId() => [
                        '<s>childString</s>&nbsp;childString2',
                    ],
                ],
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('&quot;Item #%s&quot; changed', $child->getId()),
                    ],
                ],
            ]
        );
    }

    public function testOneToManyUpdateNonReverseChild()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $child->setOwnerManyToOne($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setStringProperty('childString2');
        $em->flush();

        $this->processMessages(false, true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; changed', $child->getId()),
                        '<s>childString</s>&nbsp;childString2',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => '<s>childString</s>&nbsp;childString2',
                ],
            ]
        );
    }

    public function testOneToManyUpdateOwner()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->addChildrenOneToMany($child);
        $child->setOwnerManyToOne($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $owner->setStringProperty('ownerString2');
        $em->flush();

        $this->processMessages(false, true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>ownerString</s>&nbsp;ownerString2',
                ],
                TestAuditDataChild::class => [
                    $child->getId() => [
                        sprintf('&quot;%s&quot; changed', $owner->getId()),
                        '<s>ownerString</s>&nbsp;ownerString2',
                    ],
                ],
            ]
        );
    }

    public function testOneToOne()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->setChild($child);
        $child->setOwner($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->processMessages(false, true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; added', $child->getId()),
                        '<s></s>childString',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => [
                        sprintf('&quot;%s&quot; added', $owner->getId()),
                        '<s></s>ownerString',
                    ],
                ],
            ]
        );
    }

    public function testOneToOneUpdate()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->setChild($child);
        $child->setOwner($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setStringProperty('childString2');
        $em->flush();

        $this->processMessages(false, true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; changed', $child->getId()),
                        '<s>childString</s>&nbsp;childString2',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => [
                        '<s>childString</s>&nbsp;childString2',
                    ],
                ],
            ]
        );
    }

    public function testOneToOneUpdateOwner()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->setChild($child);
        $child->setOwner($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $owner->setStringProperty('ownerString2');
        $em->flush();

        $this->processMessages(false, true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        '<s>ownerString</s>&nbsp;ownerString2',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => [
                        sprintf('&quot;%s&quot; changed', $owner->getId()),
                        '<s>ownerString</s>&nbsp;ownerString2',
                    ],
                ],
            ]
        );
    }

    /**
     * @link https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/unitofwork-associations.html
     */
    public function testOneToOneRemove()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->setChild($child);
        $child->setOwner($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setOwner(null);
        $em->persist($child);
        $em->flush();

        self::assertEquals([], $this->getSentMessages());
    }

    public function testOneToOneRemoveCascade()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->setChildCascade($child);
        $child->setOwnerCascade($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $ownerId = $owner->getId();
        $childId = $child->getId();

        $this->getMessageCollector()->clear();
        $em->remove($child);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $ownerId => [
                        '<s>ownerString</s>&nbsp;',
                        sprintf('&quot;TestAuditDataChild::%s&quot; removed', $childId),
                    ],
                ],
                TestAuditDataChild::class => [
                    $childId => [
                        '<s>childString</s>&nbsp;',
                        sprintf('&quot;TestAuditDataOwner::%s&quot; removed', $ownerId),
                    ],
                ],
            ]
        );
    }

    public function testOneToOneRemoveCascadeOwner()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->setChildCascade($child);
        $child->setOwnerCascade($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $ownerId = $owner->getId();
        $childId = $child->getId();

        $this->getMessageCollector()->clear();
        $em->remove($owner);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $ownerId => [
                        '<s>ownerString</s>&nbsp;',
                        sprintf('&quot;TestAuditDataChild::%s&quot; removed', $childId),
                    ],
                ],
                TestAuditDataChild::class => [
                    $childId => [
                        '<s>childString</s>&nbsp;',
                        sprintf('&quot;TestAuditDataOwner::%s&quot; removed', $ownerId),
                    ],
                ],
            ]
        );
    }

    public function testOneToOneRemoveOrphan()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->setChildOrphanRemoval($child);
        $child->setOwnerOrphanRemoval($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $ownerId = $owner->getId();
        $childId = $child->getId();

        $this->getMessageCollector()->clear();
        $em->remove($child);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $ownerId => [
                        '<s>ownerString</s>&nbsp;',
                        sprintf('&quot;TestAuditDataChild::%s&quot; removed', $childId),
                    ],
                ],
                TestAuditDataChild::class => [
                    $childId => [
                        '<s>childString</s>&nbsp;',
                        sprintf('&quot;TestAuditDataOwner::%s&quot; removed', $ownerId),
                    ],
                ],
            ]
        );
    }

    public function testOneToOneRemoveOrphanOwner()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->setChildOrphanRemoval($child);
        $child->setOwnerOrphanRemoval($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $ownerId = $owner->getId();
        $childId = $child->getId();

        $this->getMessageCollector()->clear();
        $em->remove($owner);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $ownerId => [
                        '<s>ownerString</s>',
                        sprintf('&quot;TestAuditDataChild::%s&quot; removed', $childId),
                    ],
                ],
                TestAuditDataChild::class => [
                    $childId => [
                        '<s>childString</s>',
                        sprintf('&quot;TestAuditDataOwner::%s&quot; removed', $ownerId),
                    ],
                ],
            ]
        );
    }

    public function testOneToOneRemoveOwner()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->setChild($child);
        $child->setOwner($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $ownerId = $owner->getId();
        $childId = $child->getId();

        $this->getMessageCollector()->clear();
        $owner->setChild(null);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $ownerId => [
                        sprintf('&quot;Item #%s&quot; removed', $childId),
                    ],
                ],
                TestAuditDataChild::class => [
                    $childId => [
                        sprintf('&quot;%s&quot; removed', $ownerId),
                    ],
                ],
            ]
        );
    }

    public function testOneToOneReverseUpdate()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $child->setOwner($owner);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setStringProperty('childString2');
        $em->flush();

        $this->processMessages(false, true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; changed', $child->getId()),
                        '<s>childString</s>&nbsp;childString2',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => [
                        '<s>childString</s>&nbsp;childString2',
                    ],
                ],
            ]
        );
    }

    public function testOneToOneReverseUpdateOwner()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->setChild($child);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $owner->setStringProperty('ownerString2');
        $em->flush();

        $this->processMessages(false, true);

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        '<s>ownerString</s>&nbsp;ownerString2',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => [
                        sprintf('&quot;%s&quot; changed', $owner->getId()),
                        '<s>ownerString</s>&nbsp;ownerString2',
                    ],
                ],
            ]
        );
    }

    public function testOneToOneUnidirectional()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->setChildUnidirectional($child);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; added', $child->getId()),
                        '<s></s>&nbsp;ownerString',
                    ],
                ],
                TestAuditDataChild::class => [
                    $child->getId() => [
                        '<s></s>&nbsp;childString',
                    ],
                ],
            ]
        );
    }

    public function testOneToOneUnidirectionalUpdate()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');
        $owner->setChildUnidirectional($child);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $owner->setStringProperty('ownerString2');
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        '<s>ownerString</s>&nbsp;ownerString2',
                    ],
                ],
            ]
        );
    }

    public function testOneToOneUnidirectionalUpdateChild()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');
        $owner->setChildUnidirectional($child);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $child->setStringProperty('childString2');
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataChild::class => [
                    $child->getId() => [
                        '<s>childString</s>&nbsp;childString2',
                    ],
                ],
            ]
        );
    }

    public function testOneToOneUnidirectionalRemove()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('ownerString');
        $child = new TestAuditDataChild();
        $child->setStringProperty('childString');

        $owner->setChildUnidirectional($child);

        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->persist($child);
        $em->flush();

        $this->getMessageCollector()->clear();
        $owner->setChildUnidirectional(null);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => [
                        sprintf('Item #%s&quot; removed', $child->getId()),
                    ],
                ],
            ]
        );
    }

    public function testPercent()
    {
        $owner = new TestAuditDataOwner();
        $owner->setPercentProperty(0.01);
        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setPercentProperty(0.02);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>0.01</s>&nbsp;0.02',
                ],
            ]
        );
    }

    public function testSecureArray()
    {
        $this->markTestSkipped('External type');
    }

    public function testSimpleArray()
    {
        $owner = new TestAuditDataOwner();
        $owner->setSimpleArrayProperty([1 => 2, 2 => 3]);

        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setSimpleArrayProperty([1 => 3, 3 => 4]);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>2, 3</s>&nbsp;3, 4',
                ],
            ]
        );
    }

    public function testSmallint()
    {
        $owner = new TestAuditDataOwner();
        $owner->setSmallintProperty(2);

        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setSmallintProperty(3);
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>2</s>&nbsp;3',
                ],
            ]
        );
    }

    public function testString()
    {
        $owner = new TestAuditDataOwner();
        $owner->setStringProperty('string1');

        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setStringProperty('string2');
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>string1</s>&nbsp;string2',
                ],
            ]
        );
    }

    public function testText()
    {
        $owner = new TestAuditDataOwner();
        $owner->setTextProperty('string1');

        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setTextProperty('string2');
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>string1</s>&nbsp;string2',
                ],
            ]
        );
    }

    public function testTime()
    {
        $owner = new TestAuditDataOwner();
        $owner->setTimeProperty(new \DateTime('2019-01-01 00:00:00', new \DateTimeZone('UTC')));
        $em = $this->saveOwnerAndClearMessages($owner);

        $owner->setTimeProperty(new \DateTime('2019-01-02 00:00:00', new \DateTimeZone('UTC')));
        $em->flush();

        $this->processMessages();

        $this->assertData(
            [
                TestAuditDataOwner::class => [
                    $owner->getId() => '<s>12:00 AM</s>&nbsp;12:00 AM',
                ],
            ]
        );
    }

    public function testDateImmutable()
    {
        $this->markTestSkipped('BAP-18750');
    }

    public function testDateinterval()
    {
        $this->markTestSkipped('BAP-18750');
    }

    public function testDatetimeImmutable()
    {
        $this->markTestSkipped('BAP-18750');
    }

    public function testDatetimetzImmutable()
    {
        $this->markTestSkipped('BAP-18750');
    }

    public function testJson()
    {
        $this->markTestSkipped('BAP-18750');
    }

    public function testTimeImmutable()
    {
        $this->markTestSkipped('BAP-18750');
    }

    public function testWysiwyg()
    {
        $this->markTestSkipped('BAP-18750');
    }

    public function testWysiwygStyle()
    {
        $this->markTestSkipped('BAP-18750');
    }

    public function testWysiwygProperties()
    {
        $this->markTestSkipped('BAP-18750');
    }

    /**
     * <ul>
     *      <li>
     *          <b>oro.dataaudit.tests.functional.environment.entity.testauditdataowner.guid_property.label:</b>
     *          <s>ca205501-a584-4e16-bb19-0226cbb9e1c8</s>&nbsp;731d9a5c-1a42-4bb0-92f3-13f8c7824536
     *      </li>
     * </ul>
     */
    private function assertData(array $expects): void
    {
        $response = $this->client->requestGrid('audit-grid');
        $result = self::getJsonResponseContent($response, 200, $response->getContent());
        self::assertArrayHasKey('data', $result);
        $data = $result['data'];

        foreach ($data as $audit) {
            self::assertArrayHasKey('objectClass', $audit);
            self::assertArrayHasKey('objectId', $audit);

            self::assertArrayHasKey($audit['objectClass'], $expects);
            self::assertArrayHasKey($audit['objectId'], $expects[$audit['objectClass']]);

            $expect = $expects[$audit['objectClass']][$audit['objectId']];
            unset($expects[$audit['objectClass']][$audit['objectId']]);
            if (empty($expects[$audit['objectClass']])) {
                unset($expects[$audit['objectClass']]);
            }
            self::assertNotEmpty($expect);

            foreach ((array)$expect as $contains) {
                self::assertStringContainsString($contains, $audit['data']);
            }
        }
        self::assertEquals([], $expects);
    }

    private function processMessages(bool $withRelations = false, bool $withCollections = false): void
    {
        $processors[] = 'oro_dataaudit.async.audit_changed_entities';
        $processors[] = 'oro_dataaudit.async.audit_changed_entities_inverse_relations';
        if ($withRelations) {
            $processors[] = 'oro_dataaudit.async.audit_changed_entities_relations';
        }

        if ($withCollections) {
            $processors[] = 'oro_dataaudit.async.audit_changed_entities_inverse_collections';
            $processors[] = 'oro_dataaudit.async.audit_changed_entities_inverse_collections_chunk';
        }

        /** @var ConnectionInterface $connection */
        $connection = self::getContainer()->get('oro_message_queue.transport.connection');
        $session = $connection->createSession();

        foreach ($processors as $processorId) {
            /** @var AbstractAuditProcessor|TopicSubscriberInterface $processor */
            $processor = self::getContainer()->get($processorId);
            self::assertInstanceOf(AbstractAuditProcessor::class, $processor);

            foreach ($processor::getSubscribedTopics() as $topic) {
                $message = $this->getSentMessage($topic);
                $messageModel = $session->createMessage($message);
                $messageModel->setMessageId('oro_owner');
                $messageModel->setProperties([
                    Config::PARAMETER_TOPIC_NAME => $topic,
                    JobAwareTopicInterface::UNIQUE_JOB_NAME => 'fakeJobID'
                ]);

                $this->getJobProcessor()->findOrCreateRootJob(
                    $messageModel->getMessageId(),
                    $this->getJobRunner()->getJobNameByMessage($messageModel),
                    true
                );

                $processor->process($messageModel, $session);
            }
        }
    }

    private function saveOwnerAndClearMessages(TestAuditDataOwner $owner): EntityManagerInterface
    {
        $em = $this->getEntityManager();
        $em->persist($owner);
        $em->flush();
        $this->getMessageCollector()->clear();

        return $em;
    }
}
