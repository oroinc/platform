<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Provider\ExpandedAssociationExtractor;

class ExpandedAssociationExtractorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExpandedAssociationExtractor */
    protected $extractor;

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

    public function testGetExpandedAssociationsWnenAssociationShouldBeReturnedAsField()
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

    public function testGetExpandedAssociationsWnenAssociationHasMetaPropertyInAdditionalToId()
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
}
