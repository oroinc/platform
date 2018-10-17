<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Exception\DuplicateEntityAliasException;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityAliasException;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasStorage;

class EntityAliasStorageTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityAliasStorage */
    private $storage;

    protected function setUp()
    {
        $this->storage = new EntityAliasStorage();
    }

    public function testGetEntityAlias()
    {
        $entityAlias = new EntityAlias('entity1_alias', 'entity1_plural_alias');
        $this->storage->addEntityAlias('Test\Entity1', $entityAlias);

        self::assertSame(
            $entityAlias,
            $this->storage->getEntityAlias('Test\Entity1')
        );
        self::assertNull(
            $this->storage->getEntityAlias('Test\Unknown')
        );
    }

    public function testGetClassByAlias()
    {
        $this->storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('entity1_alias', 'entity1_plural_alias')
        );

        self::assertEquals(
            'Test\Entity1',
            $this->storage->getClassByAlias('entity1_alias')
        );
        self::assertNull(
            $this->storage->getClassByAlias('unknown')
        );
    }

    public function testGetClassByPluralAlias()
    {
        $this->storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('entity1_alias', 'entity1_plural_alias')
        );

        self::assertEquals(
            'Test\Entity1',
            $this->storage->getClassByPluralAlias('entity1_plural_alias')
        );
        self::assertNull(
            $this->storage->getClassByPluralAlias('unknown')
        );
    }

    public function testGetAll()
    {
        $this->storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('entity1_alias', 'entity1_plural_alias')
        );

        self::assertEquals(
            ['Test\Entity1' => new EntityAlias('entity1_alias', 'entity1_plural_alias')],
            $this->storage->getAll()
        );
    }

    public function testSerialize()
    {
        $this->storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('entity1_alias', 'entity1_plural_alias')
        );
        /** @var EntityAliasStorage $unserialized */
        $unserialized = unserialize(serialize($this->storage));
        self::assertEquals($this->storage, $unserialized);
    }

    /**
     * @dataProvider             emptyAliasDataProvider
     * @expectedException \Oro\Bundle\EntityBundle\Exception\InvalidEntityAliasException
     * @expectedExceptionMessage The alias for the "Test\Entity1" entity must not be empty.
     */
    public function testValidateEmptyEntityAlias($value)
    {
        $this->storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias($value, 'plural_alias1')
        );
    }

    /**
     * @dataProvider             emptyAliasDataProvider
     * @expectedException \Oro\Bundle\EntityBundle\Exception\InvalidEntityAliasException
     * @expectedExceptionMessage The plural alias for the "Test\Entity1" entity must not be empty.
     */
    public function testValidateEmptyEntityPluralAlias($value)
    {
        $this->storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('alias1', $value)
        );
    }

    public function emptyAliasDataProvider()
    {
        return [
            [null],
            ['']
        ];
    }

    /**
     * @dataProvider invalidAliasDataProvider
     */
    public function testValidateInvalidEntityAlias($value)
    {
        $this->expectException(InvalidEntityAliasException::class);
        $this->expectExceptionMessage(
            sprintf(
                'The string "%s" cannot be used as the alias for the "Test\Entity1" entity '
                . 'because it contains illegal characters. '
                . 'The valid alias should start with a letter and only contain '
                . 'lower case letters, numbers and underscores ("_").',
                $value
            )
        );

        $this->storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias($value, 'plural_alias1')
        );
    }

    /**
     * @dataProvider invalidAliasDataProvider
     */
    public function testValidateInvalidEntityPluralAlias($value)
    {
        $this->expectException(InvalidEntityAliasException::class);
        $this->expectExceptionMessage(
            sprintf(
                'The string "%s" cannot be used as the plural alias for the "Test\Entity1" entity '
                . 'because it contains illegal characters. '
                . 'The valid alias should start with a letter and only contain '
                . 'lower case letters, numbers and underscores ("_").',
                $value
            )
        );

        $this->storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('alias1', $value)
        );
    }

    public function invalidAliasDataProvider()
    {
        return [
            ['Alias'],
            ['my-alias'],
            ['1alias'],
        ];
    }

    public function testValidateDuplicateAliases()
    {
        $this->storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('alias', 'plural_alias1')
        );

        $this->expectException(DuplicateEntityAliasException::class);
        $this->expectExceptionMessage(
            'The alias "alias" cannot be used for the entity "Test\Entity2" because it is already '
            . 'used for the entity "Test\Entity1". To solve this problem you can use "entity_aliases" or '
            . '"entity_alias_exclusions" section in "Resources/config/oro/entity.yml" or '
            . 'create a service to provide aliases for conflicting classes and register it '
            . 'with "oro_entity.alias_provider" tag in DI container.'
        );

        $this->storage->addEntityAlias(
            'Test\Entity2',
            new EntityAlias('alias', 'plural_alias2')
        );
    }

    public function testValidateDuplicatePluralAliases()
    {
        $this->storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('alias1', 'plural_alias')
        );

        $this->expectException(DuplicateEntityAliasException::class);
        $this->expectExceptionMessage(
            'The plural alias "plural_alias" cannot be used for the entity "Test\Entity2" because it is already '
            . 'used for the entity "Test\Entity1". To solve this problem you can use "entity_aliases" or '
            . '"entity_alias_exclusions" section in "Resources/config/oro/entity.yml" or '
            . 'create a service to provide aliases for conflicting classes and register it '
            . 'with "oro_entity.alias_provider" tag in DI container.'
        );

        $this->storage->addEntityAlias(
            'Test\Entity2',
            new EntityAlias('alias2', 'plural_alias')
        );
    }

    public function testValidateDuplicateAliasAndPluralAlias()
    {
        $this->storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('alias1', 'plural_alias1')
        );

        $this->expectException(DuplicateEntityAliasException::class);
        $this->expectExceptionMessage(
            'The plural alias "alias1" cannot be used for the entity "Test\Entity2" because it is already '
            . 'used as an alias for the entity "Test\Entity1". To solve this problem you can use "entity_aliases" or '
            . '"entity_alias_exclusions" section in "Resources/config/oro/entity.yml" or '
            . 'create a service to provide aliases for conflicting classes and register it '
            . 'with "oro_entity.alias_provider" tag in DI container.'
        );

        $this->storage->addEntityAlias(
            'Test\Entity2',
            new EntityAlias('alias2', 'alias1')
        );
    }

    public function testValidateDuplicatePluralAliasAndAlias()
    {
        $this->storage->addEntityAlias(
            'Test\Entity1',
            new EntityAlias('alias1', 'plural_alias1')
        );

        $this->expectException(DuplicateEntityAliasException::class);
        $this->expectExceptionMessage(
            'The alias "plural_alias1" cannot be used for the entity "Test\Entity2" because it is already '
            . 'used as a plural alias for the entity "Test\Entity1". To solve this problem you can use "entity_aliases"'
            . ' or "entity_alias_exclusions" section in "Resources/config/oro/entity.yml" or '
            . 'create a service to provide aliases for conflicting classes and register it '
            . 'with "oro_entity.alias_provider" tag in DI container.'
        );

        $this->storage->addEntityAlias(
            'Test\Entity2',
            new EntityAlias('plural_alias1', 'plural_alias2')
        );
    }
}
