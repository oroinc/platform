<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Provider\ExpandedAssociationExtractor;

class ExpandedAssociationExtractorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpandedAssociationExtractor */
    private $extractor;

    protected function setUp()
    {
        $this->extractor = new ExpandedAssociationExtractor();
    }

    public function testGetExpandedAssociations()
    {
        $config = new EntityDefinitionConfig();
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id');
        $associationTargetConfig->addField('name');

        self::assertEquals(
            ['association' => $associationField],
            $this->extractor->getExpandedAssociations($config)
        );
    }

    public function testGetExpandedAssociationsForExcludedAssociation()
    {
        $config = new EntityDefinitionConfig();
        $associationField = $config->addField('association');
        $associationField->setExcluded();
        $associationField->setTargetClass('Test\TargetEntity');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id');
        $associationTargetConfig->addField('name');

        self::assertEquals(
            [],
            $this->extractor->getExpandedAssociations($config)
        );
    }

    public function testGetExpandedAssociationsForNotExpandedAssociation()
    {
        $config = new EntityDefinitionConfig();
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id');

        self::assertEquals(
            [],
            $this->extractor->getExpandedAssociations($config)
        );
    }

    public function testGetExpandedAssociationsForAssociationWithoutTargetClass()
    {
        $config = new EntityDefinitionConfig();
        $associationField = $config->addField('association');
        $associationField->setExcluded();
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id');
        $associationTargetConfig->addField('name');

        self::assertEquals(
            [],
            $this->extractor->getExpandedAssociations($config)
        );
    }

    public function testGetExpandedAssociationsForField()
    {
        $config = new EntityDefinitionConfig();
        $config->addField('field');

        self::assertEquals(
            [],
            $this->extractor->getExpandedAssociations($config)
        );
    }

    public function testGetExpandedAssociationsWhenAssociationShouldBeReturnedAsField()
    {
        $config = new EntityDefinitionConfig();
        $associationField = $config->addField('association');
        $associationField->setDataType('object');
        $associationField->setTargetClass('Test\TargetEntity');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id');
        $associationTargetConfig->addField('name');

        self::assertEquals(
            [],
            $this->extractor->getExpandedAssociations($config)
        );
    }

    public function testGetExpandedAssociationsWhenAssociationHasMetaPropertyInAdditionalToId()
    {
        $config = new EntityDefinitionConfig();
        $associationField = $config->addField('association');
        $associationField->setTargetClass('Test\TargetEntity');
        $associationTargetConfig = $associationField->createAndSetTargetEntity();
        $associationTargetConfig->setIdentifierFieldNames(['id']);
        $associationTargetConfig->addField('id');
        $associationTargetConfig->addField('name')->setMetaProperty(true);

        self::assertEquals(
            [],
            $this->extractor->getExpandedAssociations($config)
        );
    }

    public function testGetFirstLevelOfExpandedAssociationsByPath()
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

    public function testGetFirstLevelOfExpandedAssociationsForFieldWithoutPropertyPath()
    {
        $pathsToExpand = ['field1'];
        $config = new EntityDefinitionConfig();
        $config->addField('field1');

        self::assertEquals(
            [],
            $this->extractor->getFirstLevelOfExpandedAssociations($config, $pathsToExpand)
        );
    }

    public function testGetFirstLevelOfExpandedAssociationsForRenamedFieldWithPropertyPath()
    {
        $pathsToExpand = ['field1'];
        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setPropertyPath('realField1');

        self::assertEquals(
            [],
            $this->extractor->getFirstLevelOfExpandedAssociations($config, $pathsToExpand)
        );
    }

    public function testGetFirstLevelOfExpandedAssociationsForFieldWithPropertyPath()
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
}
