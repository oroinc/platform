<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityIdMetadataAdapter;

class EntityIdMetadataAdapterTest extends \PHPUnit\Framework\TestCase
{
    public function testGetClassName()
    {
        $className = 'Test\Class';

        $adapter = new EntityIdMetadataAdapter($className, new EntityDefinitionConfig());

        self::assertEquals($className, $adapter->getClassName());
    }

    public function testGetIdentifierFieldNames()
    {
        $identifierFieldNames = ['id'];
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames($identifierFieldNames);

        $adapter = new EntityIdMetadataAdapter('Test\Class', $config);

        self::assertEquals($identifierFieldNames, $adapter->getIdentifierFieldNames());
    }

    public function testGetPropertyPathForUnknownField()
    {
        $config = new EntityDefinitionConfig();

        $adapter = new EntityIdMetadataAdapter('Test\Class', $config);

        self::assertNull($adapter->getPropertyPath('unknown'));
    }

    public function testGetPropertyPathForNotRenamedField()
    {
        $fieldName = 'field1';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName);

        $adapter = new EntityIdMetadataAdapter('Test\Class', $config);

        self::assertEquals($fieldName, $adapter->getPropertyPath($fieldName));
    }

    public function testGetPropertyPathForRenamedField()
    {
        $fieldName = 'renamedField1';
        $fieldPropertyPath = 'field1';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setPropertyPath($fieldPropertyPath);

        $adapter = new EntityIdMetadataAdapter('Test\Class', $config);

        self::assertEquals($fieldPropertyPath, $adapter->getPropertyPath($fieldName));
    }
}
