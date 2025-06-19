<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Provider\ExpandedAssociationExtractor;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExpandedAssociationExtractorTest extends TestCase
{
    private ExpandedAssociationExtractor $extractor;

    #[\Override]
    protected function setUp(): void
    {
        $this->extractor = new ExpandedAssociationExtractor();
    }

    public function testGetExpandedAssociations(): void
    {
        $config = new EntityDefinitionConfig();
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id');
        $associationTargetConfig->addField('name');

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'association'
        ]);

        self::assertEquals(
            ['association' => $associationField],
            $this->extractor->getExpandedAssociations($config, $expandConfigExtra)
        );
    }

    public function testGetExpandedAssociationsWhenAssociationExistsInConfigButItWasNotRequested(): void
    {
        $config = new EntityDefinitionConfig();
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id');
        $associationTargetConfig->addField('name');

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'anotherAssociation'
        ]);

        self::assertEquals(
            [],
            $this->extractor->getExpandedAssociations($config, $expandConfigExtra)
        );
    }

    public function testGetExpandedAssociationsForExcludedAssociation(): void
    {
        $config = new EntityDefinitionConfig();
        $associationField = $config->addField('association');
        $associationField->setExcluded();
        $associationField->setTargetClass('Test\TargetEntity');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id');
        $associationTargetConfig->addField('name');

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'association'
        ]);

        self::assertEquals(
            [],
            $this->extractor->getExpandedAssociations($config, $expandConfigExtra)
        );
    }

    public function testGetExpandedAssociationsForAssociationThatHasOnlyIdInConfig(): void
    {
        $config = new EntityDefinitionConfig();
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id');

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'association'
        ]);

        self::assertEquals(
            [],
            $this->extractor->getExpandedAssociations($config, $expandConfigExtra)
        );
    }

    public function testGetExpandedAssociationsForAssociationWithoutTargetClass(): void
    {
        $config = new EntityDefinitionConfig();
        $associationField = $config->addField('association');
        $associationField->setExcluded();
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id');
        $associationTargetConfig->addField('name');

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'association'
        ]);

        self::assertEquals(
            [],
            $this->extractor->getExpandedAssociations($config, $expandConfigExtra)
        );
    }

    public function testGetExpandedAssociationsForField(): void
    {
        $config = new EntityDefinitionConfig();
        $config->addField('field');

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'association'
        ]);

        self::assertEquals(
            [],
            $this->extractor->getExpandedAssociations($config, $expandConfigExtra)
        );
    }

    public function testGetExpandedAssociationsWhenAssociationShouldBeReturnedAsField(): void
    {
        $config = new EntityDefinitionConfig();
        $associationField = $config->addField('association');
        $associationField->setDataType('object');
        $associationField->setTargetClass('Test\TargetEntity');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id');
        $associationTargetConfig->addField('name');

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'association'
        ]);

        self::assertEquals(
            [],
            $this->extractor->getExpandedAssociations($config, $expandConfigExtra)
        );
    }

    public function testGetExpandedAssociationsWhenAssociationHasMetaPropertyInAdditionalToId(): void
    {
        $config = new EntityDefinitionConfig();
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id');
        $associationTargetConfig->addField('name')->setMetaProperty(true);

        $expandConfigExtra = new ExpandRelatedEntitiesConfigExtra([
            'association'
        ]);

        self::assertEquals(
            [],
            $this->extractor->getExpandedAssociations($config, $expandConfigExtra)
        );
    }

    public function testGetFirstLevelOfExpandedAssociationsByPath(): void
    {
        $pathsToExpand = ['field1.field11'];
        $config = new EntityDefinitionConfig();

        self::assertEquals(
            [
                'field1' => ['field11']
            ],
            $this->extractor->getFirstLevelOfExpandedAssociations($config, $pathsToExpand)
        );
    }

    public function testGetFirstLevelOfExpandedAssociationsForFieldWithoutPropertyPath(): void
    {
        $pathsToExpand = ['field1'];
        $config = new EntityDefinitionConfig();
        $config->addField('field1');

        self::assertEquals(
            [],
            $this->extractor->getFirstLevelOfExpandedAssociations($config, $pathsToExpand)
        );
    }

    public function testGetFirstLevelOfExpandedAssociationsForNestedFieldOfFieldWithoutPropertyPath(): void
    {
        $pathsToExpand = ['field1.field3'];
        $config = new EntityDefinitionConfig();
        $config->addField('field1');

        self::assertEquals(
            [
                'field1' => ['field3']
            ],
            $this->extractor->getFirstLevelOfExpandedAssociations($config, $pathsToExpand)
        );
    }

    public function testGetFirstLevelOfExpandedAssociationsForRenamedFieldWithPropertyPath(): void
    {
        $pathsToExpand = ['field1'];
        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setPropertyPath('realField1');

        self::assertEquals(
            [],
            $this->extractor->getFirstLevelOfExpandedAssociations($config, $pathsToExpand)
        );
    }

    public function testGetFirstLevelOfExpandedAssociationsForNestedFieldOfRenamedFieldWithPropertyPath(): void
    {
        $pathsToExpand = ['field1.field3'];
        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setPropertyPath('realField1');

        self::assertEquals(
            [
                'field1' => ['field3']
            ],
            $this->extractor->getFirstLevelOfExpandedAssociations($config, $pathsToExpand)
        );
    }

    public function testGetFirstLevelOfExpandedAssociationsForFieldWithPropertyPath(): void
    {
        $pathsToExpand = ['field1'];
        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setPropertyPath('field2.field21');

        self::assertEquals(
            [
                'field2' => ['field21']
            ],
            $this->extractor->getFirstLevelOfExpandedAssociations($config, $pathsToExpand)
        );
    }

    public function testGetFirstLevelOfExpandedAssociationsForNestedFieldOfFieldWithPropertyPath(): void
    {
        $pathsToExpand = ['field1.field3'];
        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setPropertyPath('field2.field21');

        self::assertEquals(
            [
                'field2' => ['field21.field3']
            ],
            $this->extractor->getFirstLevelOfExpandedAssociations($config, $pathsToExpand)
        );
    }
}
