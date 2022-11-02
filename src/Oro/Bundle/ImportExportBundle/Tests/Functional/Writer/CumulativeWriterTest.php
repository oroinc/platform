<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Writer;

use Oro\Bundle\BatchBundle\Step\CumulativeStepExecutor;
use Oro\Bundle\BatchBundle\Test\BufferedWarningHandler;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeGroupData;
use Oro\Bundle\ImportExportBundle\Writer\CumulativeWriter;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;

/**
 * @dbIsolationPerTest
 */
class CumulativeWriterTest extends WebTestCase
{
    /** @var TestLogger */
    private $logger;

    /** @var CumulativeWriter */
    private $writer;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadAttributeData::class,
            LoadAttributeFamilyData::class,
            LoadAttributeGroupData::class
        ]);

        $this->logger = new TestLogger();
        $this->writer = $this->getContainer()->get('oro_importexport.writer.cumulative');
        $this->writer->setLogger($this->logger);
        $this->writer->setMaxChangedEntities(100);
        $this->writer->setMaxHydratedAndChangedEntities(100);
        $this->writer->setMaxHydratedEntities(100);
    }

    public function testUpdate()
    {
        $executor = new CumulativeStepExecutor();
        $executor->setWriter($this->writer);
        $executor->setProcessor(new DummyProcessor());

        $warningHandler = new BufferedWarningHandler();

        /** @var AttributeFamily $family1 */
        $family1 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        $family1->setUpdatedAt(new \DateTime('2030-05-05 00:00:00'));

        /** @var AttributeFamily $family2 */
        $family2 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2);
        $family2->setUpdatedAt(new \DateTime('2030-05-05 00:00:00'));

        $executor->setReader(new DummyReader(new \ArrayIterator([$family1, $family2])));
        $executor->execute($warningHandler);

        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Writing batch of changes to the database',
                    'context' => [
                        'ScheduledEntityUpdates' => [AttributeFamily::class => 2],
                    ],
                ],
                LogLevel::DEBUG
            )
        );
        $this->assertEquals([], $warningHandler->getWarnings());
    }

    public function testInsert()
    {
        $executor = new CumulativeStepExecutor();
        $executor->setWriter($this->writer);
        $executor->setProcessor(new DummyProcessor());

        $warningHandler = new BufferedWarningHandler();

        $entities = [];
        for ($i = 1; $i <= 100; $i++) {
            $entity = new AttributeFamily();
            $entity->setCode($i);
            $entity->setEntityClass(AttributeFamily::class);
            $entities[] = $entity;
        }
        $iterator = new \ArrayIterator($entities);

        $executor->setReader(new DummyReader($iterator));
        $executor->execute($warningHandler);

        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Writing batch of changes to the database',
                    'context' => [
                        'ScheduledEntityInsertions' => [AttributeFamily::class => 100],
                    ],
                ],
                LogLevel::DEBUG
            )
        );
        $this->assertEquals([], $warningHandler->getWarnings());
    }

    public function testDelete()
    {
        $executor = new CumulativeStepExecutor();
        $executor->setWriter($this->writer);
        $executor->setProcessor(new DummyProcessor());

        $warningHandler = new BufferedWarningHandler();

        /** @var AttributeFamily $family1 */
        $family1 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        $group1 = $family1->getAttributeGroup(LoadAttributeGroupData::REGULAR_ATTRIBUTE_GROUP_1);
        $family1->removeAttributeGroup($group1);
        $this->getContainer()->get('doctrine')->getManager()->remove($group1);

        /** @var AttributeFamily $family2 */
        $family2 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2);
        $group2 = $family2->getAttributeGroup(LoadAttributeGroupData::REGULAR_ATTRIBUTE_GROUP_2);
        $family2->removeAttributeGroup($group2);
        $this->getContainer()->get('doctrine')->getManager()->remove($group2);

        $executor->setReader(new DummyReader(new \ArrayIterator([$family1, $family2])));
        $executor->execute($warningHandler);

        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Writing batch of changes to the database',
                    'context' => [
                        'ScheduledEntityDeletions' => [
                            LocalizedFallbackValue::class => 2,
                            AttributeGroupRelation::class => 2,
                            AttributeGroup::class => 2,
                        ],
                    ],
                ],
                LogLevel::DEBUG
            )
        );
        $this->assertEquals([], $warningHandler->getWarnings());
    }

    public function testCollection()
    {
        $executor = new CumulativeStepExecutor();
        $executor->setWriter($this->writer);
        $executor->setProcessor(new DummyProcessor());

        $warningHandler = new BufferedWarningHandler();

        /** @var AttributeFamily $family1 */
        $family1 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        $label = new LocalizedFallbackValue();
        $label->setString('new label');
        $family1->getLabels()->clear();
        $family1->addLabel($label);

        $family1->getLabel()->setString('test1');
        /** @var AttributeFamily $family2 */
        $family2 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2);
        $label = new LocalizedFallbackValue();
        $label->setString('new label');
        $family2->getLabels()->clear();
        $family2->addLabel($label);

        $executor->setReader(new DummyReader(new \ArrayIterator([$family1, $family2])));
        $executor->execute($warningHandler);

        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Writing batch of changes to the database',
                    'context' => [
                        'ScheduledEntityInsertions' => [
                            LocalizedFallbackValue::class => 2,
                        ],
                        'ScheduledCollectionDeletions' => [
                            AttributeFamily::class.'#labels' => 2,
                        ],
                    ],
                ],
                LogLevel::DEBUG
            )
        );
        $this->assertEquals([], $warningHandler->getWarnings());
    }

    public function testNoFlushWithoutChanges()
    {
        $executor = new CumulativeStepExecutor();
        $executor->setWriter($this->writer);
        $executor->setProcessor(new DummyProcessor());

        $warningHandler = new BufferedWarningHandler();

        /** @var AttributeFamily $family1 */
        $family1 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);

        /** @var AttributeFamily $family2 */
        $family2 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2);

        $executor->setReader(new DummyReader(new \ArrayIterator([$family1, $family2])));
        $executor->execute($warningHandler);

        $this->assertEquals([], $warningHandler->getWarnings());
        $this->assertEquals([], $this->logger->records);
    }

    public function testDoNotIgnoreChanges()
    {
        $entities = [];
        for ($i = 1; $i <= 100; $i++) {
            $entity = new AttributeFamily();
            $entity->setCode($i);
            $entity->setEntityClass(AttributeFamily::class);
            $entities[] = $entity;
        }

        $this->writer->write($entities);
        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Writing batch of changes to the database',
                    'context' => [
                        'ScheduledEntityInsertions' => [AttributeFamily::class => 100],
                    ],
                ],
                LogLevel::DEBUG
            )
        );
        $this->logger->reset();

        $this->writer->close();
        $this->assertEquals([], $this->logger->records);
    }

    public function testIgnoreChanges()
    {
        $this->writer->addIgnoredChange(AttributeFamily::class);

        $entities = [];
        for ($i = 1; $i <= 100; $i++) {
            $entity = new AttributeFamily();
            $entity->setCode($i);
            $entity->setEntityClass(AttributeFamily::class);
            $entities[] = $entity;
        }

        $this->writer->write($entities);
        $this->assertEquals([], $this->logger->records);

        $this->writer->close();
        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Writing batch of changes to the database',
                    'context' => [
                        'ScheduledEntityInsertions' => [AttributeFamily::class => 100],
                    ],
                ],
                LogLevel::DEBUG
            )
        );
    }
}
