<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityIdMetadataAdapter;
use PHPUnit\Framework\TestCase;

class EntityIdMetadataAdapterTest extends TestCase
{
    public function testGetClassName(): void
    {
        $className = 'Test\Class';

        $adapter = new EntityIdMetadataAdapter($className, new EntityDefinitionConfig());

        self::assertEquals($className, $adapter->getClassName());
    }

    public function testGetIdentifierFieldNames(): void
    {
        $identifierFieldNames = ['id'];
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames($identifierFieldNames);

        $adapter = new EntityIdMetadataAdapter('Test\Class', $config);

        self::assertEquals($identifierFieldNames, $adapter->getIdentifierFieldNames());
    }

    public function testGetPropertyPathForUnknownField(): void
    {
        $config = new EntityDefinitionConfig();

        $adapter = new EntityIdMetadataAdapter('Test\Class', $config);

        self::assertNull($adapter->getPropertyPath('unknown'));
    }

    public function testGetPropertyPathForNotRenamedField(): void
    {
        $fieldName = 'field1';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName);

        $adapter = new EntityIdMetadataAdapter('Test\Class', $config);

        self::assertEquals($fieldName, $adapter->getPropertyPath($fieldName));
    }

    public function testGetPropertyPathForRenamedField(): void
    {
        $fieldName = 'renamedField1';
        $fieldPropertyPath = 'field1';
        $config = new EntityDefinitionConfig();
        $config->addField($fieldName)->setPropertyPath($fieldPropertyPath);

        $adapter = new EntityIdMetadataAdapter('Test\Class', $config);

        self::assertEquals($fieldPropertyPath, $adapter->getPropertyPath($fieldName));
    }

    public function testGetHintsWhenNoHintsInConfig(): void
    {
        $adapter = new EntityIdMetadataAdapter('Test\Class', new EntityDefinitionConfig());

        self::assertSame([], $adapter->getHints());
    }

    public function testGetHintsWhenConfigHasHints(): void
    {
        $config = new EntityDefinitionConfig();
        $config->addHint('HINT_TEST');

        $adapter = new EntityIdMetadataAdapter('Test\Class', $config);

        self::assertSame(['HINT_TEST'], $adapter->getHints());
    }
}
