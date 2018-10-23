<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\EntityAliasStorage;
use Oro\Bundle\EntityBundle\Exception\DuplicateEntityAliasException;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityAliasException;
use Oro\Bundle\EntityBundle\Model\EntityAlias;

class EntityAliasStorageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider validAliasDataProvider
     */
    public function testValidateValidEntityAlias($value)
    {
        $storage = new EntityAliasStorage(['api.yml']);

        $entityAlias = new EntityAlias($value, $value);
        $storage->addEntityAlias('Test\Entity1', $entityAlias);

        self::assertSame(
            $entityAlias,
            $storage->getEntityAlias('Test\Entity1')
        );
    }

    public function validAliasDataProvider()
    {
        return [
            ['alias'],
            ['my-alias'],
            ['my_alias']
        ];
    }

    /**
     * @dataProvider invalidAliasDataProvider
     */
    public function testValidateInvalidEntityAlias($value)
    {
        $storage = new EntityAliasStorage(['api.yml']);

        $this->expectException(InvalidEntityAliasException::class);
        $this->expectExceptionMessage(
            sprintf(
                'The string "%s" cannot be used as the alias for the "Test\Entity1" entity '
                . 'because it contains illegal characters. '
                . 'The valid alias should start with a letter and only contain '
                . 'lower case letters, numbers, hyphens ("-") and underscores ("_").',
                $value
            )
        );

        $storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias($value, 'plural_alias1')
        );
    }

    /**
     * @dataProvider invalidAliasDataProvider
     */
    public function testValidateInvalidEntityPluralAlias($value)
    {
        $storage = new EntityAliasStorage(['api.yml']);

        $this->expectException(InvalidEntityAliasException::class);
        $this->expectExceptionMessage(
            sprintf(
                'The string "%s" cannot be used as the plural alias for the "Test\Entity1" entity '
                . 'because it contains illegal characters. '
                . 'The valid alias should start with a letter and only contain '
                . 'lower case letters, numbers, hyphens ("-") and underscores ("_").',
                $value
            )
        );

        $storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('alias1', $value)
        );
    }

    public function invalidAliasDataProvider()
    {
        return [
            ['Alias'],
            ['my alias'],
            ['1alias'],
            ['alias@']
        ];
    }

    public function testGetDuplicateAliasHelpMessageForEmptyConfigFiles()
    {
        $storage = new EntityAliasStorage([]);

        $storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('alias', 'plural_alias')
        );

        $this->expectException(DuplicateEntityAliasException::class);
        $this->expectExceptionMessage(
            'The alias "alias" cannot be used for the entity "Test\Entity2" because it is already '
            . 'used for the entity "Test\Entity1". To solve this problem you can use "entity_aliases" or '
            . '"entity_alias_exclusions" section in "Resources/config/oro/entity.yml" or '
            . 'create a service to provide aliases for conflicting classes and register it '
            . 'with "oro_entity.alias_provider" tag in DI container.'
        );

        $storage->addEntityAlias(
            'Test\Entity2',
            new EntityAlias('alias', 'plural_alias')
        );
    }

    public function testGetDuplicateAliasHelpMessageForOneConfigFile()
    {
        $storage = new EntityAliasStorage(['api.yml']);

        $storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('alias', 'plural_alias')
        );

        $this->expectException(DuplicateEntityAliasException::class);
        $this->expectExceptionMessage(
            'The alias "alias" cannot be used for the entity "Test\Entity2" because it is already '
            . 'used for the entity "Test\Entity1". To solve this problem you can '
            . 'use "entity_aliases" section in "Resources/config/oro/api.yml", '
            . 'use "entity_aliases" or "entity_alias_exclusions" section in "Resources/config/oro/entity.yml" or '
            . 'create a service to provide aliases for conflicting classes and register it '
            . 'with "oro_entity.alias_provider" tag in DI container.'
        );

        $storage->addEntityAlias(
            'Test\Entity2',
            new EntityAlias('alias', 'plural_alias')
        );
    }

    public function testGetDuplicateAliasHelpMessageForTwoConfigFiles()
    {
        $storage = new EntityAliasStorage(['first.yml', 'second.yml']);

        $storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('alias', 'plural_alias')
        );

        $this->expectException(DuplicateEntityAliasException::class);
        $this->expectExceptionMessage(
            'The alias "alias" cannot be used for the entity "Test\Entity2" because it is already '
            . 'used for the entity "Test\Entity1". To solve this problem you can '
            . 'use "entity_aliases" section in "Resources/config/oro/first.yml" or "Resources/config/oro/second.yml", '
            . 'use "entity_aliases" or "entity_alias_exclusions" section in "Resources/config/oro/entity.yml" or '
            . 'create a service to provide aliases for conflicting classes and register it '
            . 'with "oro_entity.alias_provider" tag in DI container.'
        );

        $storage->addEntityAlias(
            'Test\Entity2',
            new EntityAlias('alias', 'plural_alias')
        );
    }

    public function testGetDuplicateAliasHelpMessageForThreeAndMoreConfigFiles()
    {
        $storage = new EntityAliasStorage(['first.yml', 'second.yml', 'third.yml']);

        $storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('alias', 'plural_alias')
        );

        $this->expectException(DuplicateEntityAliasException::class);
        $this->expectExceptionMessage(
            'The alias "alias" cannot be used for the entity "Test\Entity2" because it is already '
            . 'used for the entity "Test\Entity1". To solve this problem you can '
            . 'use "entity_aliases" section in "Resources/config/oro/first.yml", "Resources/config/oro/second.yml" '
            . 'or "Resources/config/oro/third.yml", '
            . 'use "entity_aliases" or "entity_alias_exclusions" section in "Resources/config/oro/entity.yml" or '
            . 'create a service to provide aliases for conflicting classes and register it '
            . 'with "oro_entity.alias_provider" tag in DI container.'
        );

        $storage->addEntityAlias(
            'Test\Entity2',
            new EntityAlias('alias', 'plural_alias')
        );
    }
}
