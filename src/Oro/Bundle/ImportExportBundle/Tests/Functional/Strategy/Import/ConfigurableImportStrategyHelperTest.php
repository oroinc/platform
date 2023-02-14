<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Strategy\Import;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeFamilyData;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeGroupData;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableImportStrategyHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;

/**
 * @dbIsolationPerTest
 */
class ConfigurableImportStrategyHelperTest extends WebTestCase
{
    /** @var TestLogger */
    private $logger;

    /** @var ConfigurableImportStrategyHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadAttributeData::class,
            LoadAttributeFamilyData::class,
            LoadAttributeGroupData::class
        ]);

        $this->logger = new TestLogger();
        $this->helper = $this->getContainer()->get('oro_importexport.strategy.configurable_import_strategy_helper');
        $this->helper->setLogger($this->logger);
    }

    public function testChangeExistingEntity()
    {
        /** @var AttributeFamily $family1 */
        $family1 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        /** @var AttributeFamily $family2 */
        $family2 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2);

        $familyNormalized = new AttributeFamily();
        $familyNormalized->setCode($family2->getCode());
        foreach ($family2->getLabels() as $label) {
            $familyNormalized->addLabel($label);
        }
        foreach ($family2->getAttributeGroups() as $group) {
            $familyNormalized->addAttributeGroup($group);
        }

        $this->helper->importEntity($family1, $familyNormalized);

        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Property changed during import.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'code',
                        'databaseValue' => 's:18:"attribute_family_1";',
                        'importedValue' => 's:18:"attribute_family_2";',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Property changed during import.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'labels',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Property changed during import.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'attributeGroups',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
    }

    public function testChangeExistingEntityDoNotInitializeExcludedCollection()
    {
        /** @var AttributeFamily $family1 */
        $family1 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        /** @var AttributeFamily $family2 */
        $family2 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2);
        $familyNormalized = new AttributeFamily();
        $familyNormalized->setCode($family2->getCode());

        $this->helper->importEntity($family1, $familyNormalized, ['attributeGroups']);

        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Property initialized but not changed during import.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'labels',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
        $this->assertFalse(
            $this->logger->hasRecord(
                [
                    'message' => 'Property changed during import.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'attributeGroups',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
        $this->assertFalse(
            $this->logger->hasRecord(
                [
                    'message' => 'Property initialized but not changed during import.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'attributeGroups',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
    }

    public function testChangeNewEntity()
    {
        /** @var AttributeFamily $family1 */
        $family1 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        /** @var AttributeFamily $family2 */
        $family2 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2);

        $familyNew = new AttributeFamily();
        $familyNew->setCode($family1->getCode());
        foreach ($family1->getLabels() as $label) {
            $familyNew->addLabel($label);
        }
        foreach ($family1->getAttributeGroups() as $group) {
            $familyNew->addAttributeGroup($group);
        }

        $familyNormalized = new AttributeFamily();
        $familyNormalized->setCode($family2->getCode());
        foreach ($family2->getLabels() as $label) {
            $familyNormalized->addLabel($label);
        }
        foreach ($family2->getAttributeGroups() as $group) {
            $familyNormalized->addAttributeGroup($group);
        }

        $this->helper->importEntity($familyNew, $familyNormalized);

        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Property changed during import.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'code',
                        'databaseValue' => 's:18:"attribute_family_1";',
                        'importedValue' => 's:18:"attribute_family_2";',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
    }

    public function testChangeNewEntityDoNotInitializeAnyCollection()
    {
        /** @var AttributeFamily $family1 */
        $family1 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        /** @var AttributeFamily $family2 */
        $family2 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_2);

        $familyNew = new AttributeFamily();
        $familyNew->setCode($family1->getCode());
        foreach ($family1->getLabels() as $label) {
            $familyNew->addLabel($label);
        }
        foreach ($family1->getAttributeGroups() as $group) {
            $familyNew->addAttributeGroup($group);
        }

        $familyNormalized = new AttributeFamily();
        $familyNormalized->setCode($family2->getCode());

        $this->helper->importEntity($familyNew, $familyNormalized);

        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Property changed during import.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'code',
                        'databaseValue' => 's:18:"attribute_family_1";',
                        'importedValue' => 's:18:"attribute_family_2";',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
        $this->assertFalse(
            $this->logger->hasRecord(
                [
                    'message' => 'Property changed during import.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'attributeGroups',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
        $this->assertFalse(
            $this->logger->hasRecord(
                [
                    'message' => 'Property initialized but not changed during import.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'attributeGroups',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
    }

    public function testChangeDate()
    {
        $family1 = new AttributeFamily();
        $family1->setUpdatedAt(new \DateTime('2030-05-05 00:00:00', new \DateTimeZone('UTC')));
        $family2 = new AttributeFamily();
        $family2->setUpdatedAt(new \DateTime('2030-05-05 00:00:00', new \DateTimeZone('UTC')));

        $this->helper->importEntity($family1, $family2);
        $this->assertFalse($this->logger->hasRecords(LogLevel::DEBUG));

        $family2->setUpdatedAt(new \DateTime('2040-05-05 00:00:00', new \DateTimeZone('UTC')));
        $this->helper->importEntity($family1, $family2);

        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Property changed during import.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'updatedAt',
                        'databaseValue' => 'O:8:"DateTime":3:{s:4:"date";s:26:"2030-05-05 00:00:00.000000";s:13:'.
                            '"timezone_type";i:3;s:8:"timezone";s:3:"UTC";}',
                        'importedValue' => 'O:8:"DateTime":3:{s:4:"date";s:26:"2040-05-05 00:00:00.000000";s:13:'.
                            '"timezone_type";i:3;s:8:"timezone";s:3:"UTC";}',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
    }

    public function testChangeOwner()
    {
        /** @var AttributeFamily $family1 */
        $family1 = $this->getReference(LoadAttributeFamilyData::ATTRIBUTE_FAMILY_1);
        $id = $family1->getOwner()->getId();

        $family2 = new AttributeFamily();
        $family2->setCode($family1->getCode());

        $this->helper->importEntity($family1, $family2);

        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Property changed during import.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'owner',
                        'databaseValue' => serialize(['id' => $id]),
                        'importedValue' => 'N;',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
    }

    public function testTypeMismatch()
    {
        $family1 = new AttributeFamily();
        $family1->setIsEnabled(true);
        $family2 = new AttributeFamily();
        $family2->setIsEnabled(1);

        $this->helper->importEntity($family1, $family2);

        $this->assertIsBool($family1->getIsEnabled());
        $this->assertTrue($family1->getIsEnabled());

        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Property not changed during import, but type does not match.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'isEnabled',
                        'databaseValue' => 'b:1;',
                        'importedValue' => 'i:1;',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
    }

    public function testTypeMatch()
    {
        $family1 = new AttributeFamily();
        $family1->setIsEnabled(true);
        $family2 = new AttributeFamily();
        $family2->setIsEnabled(false);

        $this->helper->importEntity($family1, $family2);

        $this->assertIsBool($family1->getIsEnabled());
        $this->assertFalse($family1->getIsEnabled());

        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Property changed during import.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'isEnabled',
                        'databaseValue' => 'b:1;',
                        'importedValue' => 'b:0;',
                    ],
                ],
                LogLevel::DEBUG
            )
        );
    }

    public function testZeroLeadingString(): void
    {
        $family1 = new AttributeFamily();
        $family1->setCode('0000123');
        $family2 = new AttributeFamily();
        $family2->setCode('00123');

        $this->helper->importEntity($family1, $family2);

        $this->assertIsString($family1->getCode());
        $this->assertEquals('00123', $family1->getCode());

        $this->assertTrue(
            $this->logger->hasRecord(
                [
                    'message' => 'Property changed during import.',
                    'context' => [
                        'databaseEntityClass' => AttributeFamily::class,
                        'propertyName' => 'code',
                        'databaseValue' => serialize('0000123'),
                        'importedValue' => serialize('00123'),
                    ],
                ],
                LogLevel::DEBUG
            )
        );
    }

    public function testDenormalizedPropertyAwareImport()
    {
        $dbEntity = new Product();
        $dbEntity->setName('Db');
        $dbEntity->updateDenormalizedProperties();

        $this->assertEquals('DB', $dbEntity->getDenormalizedName());
        $this->assertEquals('db', $dbEntity->getNameLowercase());

        $importEntity = new Product();
        $importEntity->setName('Import');

        $this->helper->importEntity($dbEntity, $importEntity, ['denormalizedName', 'nameLowercase']);

        $this->assertEquals('IMPORT', $dbEntity->getDenormalizedName());
        $this->assertEquals('import', $dbEntity->getNameLowercase());
    }
}
