<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

class EntityAliasResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataFactory;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    protected function setUp()
    {
        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->em       = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($this->em);
        $this->metadataFactory = $this->getMockBuilder('Doctrine\Common\Persistence\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($this->metadataFactory);

        $this->entityAliasResolver = new EntityAliasResolver($this->doctrine, true);
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException
     * @expectedExceptionMessage An alias for "Test\UnknownEntity" entity not found.
     */
    public function testGetAliasForUnknownEntity()
    {
        $this->entityAliasResolver->getAlias('Test\UnknownEntity');
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException
     * @expectedExceptionMessage An alias for "Test\UnknownEntity" entity not found.
     */
    public function testGetAliasCacheForUnknownEntity()
    {
        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with('Test\UnknownEntity')
            ->willReturn(null);
        $this->entityAliasResolver->addProvider($entityAliasProvider);

        try {
            $this->entityAliasResolver->getAlias('Test\UnknownEntity');
        } catch (EntityAliasNotFoundException $e) {
            // ignore the first call
        }

        // test that the result is cached
        $this->entityAliasResolver->getAlias('Test\UnknownEntity');
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException
     * @expectedExceptionMessage A plural alias for "Test\UnknownEntity" entity not found.
     */
    public function testGetPluralAliasForUnknownEntity()
    {
        $this->entityAliasResolver->getPluralAlias('Test\UnknownEntity');
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException
     * @expectedExceptionMessage A plural alias for "Test\UnknownEntity" entity not found.
     */
    public function testGetPluralAliasCacheForUnknownEntity()
    {
        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with('Test\UnknownEntity')
            ->willReturn(null);
        $this->entityAliasResolver->addProvider($entityAliasProvider);

        try {
            $this->entityAliasResolver->getPluralAlias('Test\UnknownEntity');
        } catch (EntityAliasNotFoundException $e) {
            // ignore the first call
        }

        // test that the result is cached
        $this->entityAliasResolver->getPluralAlias('Test\UnknownEntity');
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException
     * @expectedExceptionMessage The alias "unknown" is not associated with any entity class.
     */
    public function testGetClassByAliasForUnknownAlias()
    {
        $this->metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([]);

        $this->entityAliasResolver->getClassByAlias('unknown');
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException
     * @expectedExceptionMessage The alias "unknown" is not associated with any entity class.
     */
    public function testGetClassByAliasCacheForUnknownAlias()
    {
        $this->metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([]);

        try {
            $this->entityAliasResolver->getClassByAlias('unknown');
        } catch (EntityAliasNotFoundException $e) {
            // ignore the first fail
        }

        // test that the result is cached
        $this->entityAliasResolver->getClassByAlias('unknown');
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException
     * @expectedExceptionMessage The plural alias "unknown" is not associated with any entity class.
     */
    public function testGetClassByPluralAliasForUnknownAlias()
    {
        $this->metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([]);

        $this->entityAliasResolver->getClassByPluralAlias('unknown');
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException
     * @expectedExceptionMessage The plural alias "unknown" is not associated with any entity class.
     */
    public function testGetClassByPluralAliasCacheForUnknownAlias()
    {
        $this->metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([]);

        try {
            $this->entityAliasResolver->getClassByPluralAlias('unknown');
        } catch (EntityAliasNotFoundException $e) {
            // ignore the first fail
        }

        // test that the result is cached
        $this->entityAliasResolver->getClassByPluralAlias('unknown');
    }

    public function testGetAllForEmptyEntityManager()
    {
        $this->metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([]);

        $this->assertSame(
            [],
            $this->entityAliasResolver->getAll()
        );
        // test that a result of getAllMetadata is cached
        $this->assertSame(
            [],
            $this->entityAliasResolver->getAll()
        );
    }

    public function testWarmUp()
    {
        $this->metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([]);

        $this->entityAliasResolver->warmUp('cache/dir');
        // test that a result of getAllMetadata is cached
        $this->entityAliasResolver->warmUp('cache/dir');
    }

    public function testGetAlias()
    {
        $this->initialiseAliases(0);

        $this->assertEquals(
            'entity1_alias',
            $this->entityAliasResolver->getAlias('Test\Entity1')
        );
    }

    public function testGetAliasCache()
    {
        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with('Test\Entity1')
            ->willReturn(new EntityAlias('entity1_alias', 'entity1_plural_alias'));
        $this->entityAliasResolver->addProvider($entityAliasProvider);

        $this->assertEquals(
            'entity1_alias',
            $this->entityAliasResolver->getAlias('Test\Entity1')
        );

        // test that the result is cached
        $this->assertEquals(
            'entity1_alias',
            $this->entityAliasResolver->getAlias('Test\Entity1')
        );
    }

    public function testGetPluralAlias()
    {
        $this->initialiseAliases(0);

        $this->assertEquals(
            'entity1_plural_alias',
            $this->entityAliasResolver->getPluralAlias('Test\Entity1')
        );
    }

    public function testGetPluralAliasCache()
    {
        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with('Test\Entity1')
            ->willReturn(new EntityAlias('entity1_alias', 'entity1_plural_alias'));
        $this->entityAliasResolver->addProvider($entityAliasProvider);

        $this->assertEquals(
            'entity1_plural_alias',
            $this->entityAliasResolver->getPluralAlias('Test\Entity1')
        );

        // test that the result is cached
        $this->assertEquals(
            'entity1_plural_alias',
            $this->entityAliasResolver->getPluralAlias('Test\Entity1')
        );
    }

    public function testGetClassByAlias()
    {
        $this->initialiseAliases(1);

        $this->assertEquals(
            'Test\Entity1',
            $this->entityAliasResolver->getClassByAlias('entity1_alias')
        );
    }

    public function testGetClassByAliasCache()
    {
        $this->metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([new ClassMetadata('Test\Entity1')]);

        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with('Test\Entity1')
            ->willReturn(new EntityAlias('entity1_alias', 'entity1_plural_alias'));
        $this->entityAliasResolver->addProvider($entityAliasProvider);

        $this->assertEquals(
            'Test\Entity1',
            $this->entityAliasResolver->getClassByAlias('entity1_alias')
        );

        // test that the result is cached
        $this->assertEquals(
            'Test\Entity1',
            $this->entityAliasResolver->getClassByAlias('entity1_alias')
        );
    }

    public function testGetClassByPluralAlias()
    {
        $this->initialiseAliases(1);

        $this->assertEquals(
            'Test\Entity1',
            $this->entityAliasResolver->getClassByPluralAlias('entity1_plural_alias')
        );
    }

    public function testGetClassByPluralAliasCache()
    {
        $this->metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([new ClassMetadata('Test\Entity1')]);

        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with('Test\Entity1')
            ->willReturn(new EntityAlias('entity1_alias', 'entity1_plural_alias'));
        $this->entityAliasResolver->addProvider($entityAliasProvider);

        $this->assertEquals(
            'Test\Entity1',
            $this->entityAliasResolver->getClassByPluralAlias('entity1_plural_alias')
        );

        // test that the result is cached
        $this->assertEquals(
            'Test\Entity1',
            $this->entityAliasResolver->getClassByPluralAlias('entity1_plural_alias')
        );
    }

    public function testGetAll()
    {
        $this->initialiseAliases(1);

        $this->assertEquals(
            [
                'Test\Entity1' => new EntityAlias('entity1_alias', 'entity1_plural_alias'),
                'Test\Entity2' => new EntityAlias('entity2_alias', 'entity2_plural_alias')
            ],
            $this->entityAliasResolver->getAll()
        );
        // test that a result of getAllMetadata is cached
        $this->assertEquals(
            [
                'Test\Entity1' => new EntityAlias('entity1_alias', 'entity1_plural_alias'),
                'Test\Entity2' => new EntityAlias('entity2_alias', 'entity2_plural_alias')
            ],
            $this->entityAliasResolver->getAll()
        );
    }

    public function testValidateDuplicateAliases()
    {
        $this->metadataFactory->expects($this->any())
            ->method('getAllMetadata')
            ->willReturn(
                [
                    new ClassMetadata('Test\Entity1'),
                    new ClassMetadata('Test\Entity2')
                ]
            );

        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->any())
            ->method('getEntityAlias')
            ->willReturnMap(
                [
                    ['Test\Entity1', new EntityAlias('alias', 'plural_alias1')],
                    ['Test\Entity2', new EntityAlias('alias', 'plural_alias2')]
                ]
            );

        $this->entityAliasResolver->addProvider($entityAliasProvider);

        $this->setExpectedException(
            '\Oro\Bundle\EntityBundle\Exception\RuntimeException',
            'The alias "alias" cannot be used for the entity "Test\Entity2" because it is already '
            . 'used for the entity "Test\Entity1". To solve this problem you can use "entity_aliases" or '
            . '"entity_alias_exclusions" section in the "Resources/config/oro/entity.yml" of your bundle or '
            . 'create a service to provide aliases for conflicting classes and register it '
            . 'with the tag "oro_entity.alias_provider" in DI container.'
        );

        $this->entityAliasResolver->getAll();
    }

    public function testValidateDuplicateAliasesNoDebug()
    {
        $this->entityAliasResolver = new EntityAliasResolver($this->doctrine, false);

        $this->metadataFactory->expects($this->any())
            ->method('getAllMetadata')
            ->willReturn(
                [
                    new ClassMetadata('Test\Entity1'),
                    new ClassMetadata('Test\Entity2')
                ]
            );

        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->any())
            ->method('getEntityAlias')
            ->willReturnMap(
                [
                    ['Test\Entity1', new EntityAlias('alias', 'plural_alias1')],
                    ['Test\Entity2', new EntityAlias('alias', 'plural_alias2')]
                ]
            );

        $this->entityAliasResolver->addProvider($entityAliasProvider);

        $this->assertEquals(
            [
                'Test\Entity1' => new EntityAlias('alias', 'plural_alias1')
            ],
            $this->entityAliasResolver->getAll()
        );
    }

    public function testValidateDuplicatePluralAliases()
    {
        $this->metadataFactory->expects($this->any())
            ->method('getAllMetadata')
            ->willReturn(
                [
                    new ClassMetadata('Test\Entity1'),
                    new ClassMetadata('Test\Entity2')
                ]
            );

        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->any())
            ->method('getEntityAlias')
            ->willReturnMap(
                [
                    ['Test\Entity1', new EntityAlias('alias1', 'plural_alias')],
                    ['Test\Entity2', new EntityAlias('alias2', 'plural_alias')]
                ]
            );

        $this->entityAliasResolver->addProvider($entityAliasProvider);

        $this->setExpectedException(
            '\Oro\Bundle\EntityBundle\Exception\RuntimeException',
            'The plural alias "plural_alias" cannot be used for the entity "Test\Entity2" because it is already '
            . 'used for the entity "Test\Entity1". To solve this problem you can use "entity_aliases" or '
            . '"entity_alias_exclusions" section in the "Resources/config/oro/entity.yml" of your bundle or '
            . 'create a service to provide aliases for conflicting classes and register it '
            . 'with the tag "oro_entity.alias_provider" in DI container.'
        );

        $this->entityAliasResolver->getAll();
    }

    public function testValidateDuplicatePluralAliasesNoDebug()
    {
        $this->entityAliasResolver = new EntityAliasResolver($this->doctrine, false);

        $this->metadataFactory->expects($this->any())
            ->method('getAllMetadata')
            ->willReturn(
                [
                    new ClassMetadata('Test\Entity1'),
                    new ClassMetadata('Test\Entity2')
                ]
            );

        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->any())
            ->method('getEntityAlias')
            ->willReturnMap(
                [
                    ['Test\Entity1', new EntityAlias('alias1', 'plural_alias')],
                    ['Test\Entity2', new EntityAlias('alias2', 'plural_alias')]
                ]
            );

        $this->entityAliasResolver->addProvider($entityAliasProvider);

        $this->assertEquals(
            [
                'Test\Entity1' => new EntityAlias('alias1', 'plural_alias')
            ],
            $this->entityAliasResolver->getAll()
        );
    }

    public function testValidateDuplicateAliasAndPluralAlias()
    {
        $this->metadataFactory->expects($this->any())
            ->method('getAllMetadata')
            ->willReturn(
                [
                    new ClassMetadata('Test\Entity1'),
                    new ClassMetadata('Test\Entity2')
                ]
            );

        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->any())
            ->method('getEntityAlias')
            ->willReturnMap(
                [
                    ['Test\Entity1', new EntityAlias('alias1', 'plural_alias1')],
                    ['Test\Entity2', new EntityAlias('alias2', 'alias1')]
                ]
            );

        $this->entityAliasResolver->addProvider($entityAliasProvider);

        $this->setExpectedException(
            '\Oro\Bundle\EntityBundle\Exception\RuntimeException',
            'The plural alias "alias1" cannot be used for the entity "Test\Entity2" because it is already '
            . 'used as an alias for the entity "Test\Entity1". To solve this problem you can use "entity_aliases" or '
            . '"entity_alias_exclusions" section in the "Resources/config/oro/entity.yml" of your bundle or '
            . 'create a service to provide aliases for conflicting classes and register it '
            . 'with the tag "oro_entity.alias_provider" in DI container.'
        );

        $this->entityAliasResolver->getAll();
    }

    public function testValidateDuplicateAliasAndPluralAliasNoDebug()
    {
        $this->entityAliasResolver = new EntityAliasResolver($this->doctrine, false);

        $this->metadataFactory->expects($this->any())
            ->method('getAllMetadata')
            ->willReturn(
                [
                    new ClassMetadata('Test\Entity1'),
                    new ClassMetadata('Test\Entity2')
                ]
            );

        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->any())
            ->method('getEntityAlias')
            ->willReturnMap(
                [
                    ['Test\Entity1', new EntityAlias('alias1', 'plural_alias1')],
                    ['Test\Entity2', new EntityAlias('alias2', 'alias1')]
                ]
            );

        $this->entityAliasResolver->addProvider($entityAliasProvider);

        $this->assertEquals(
            [
                'Test\Entity1' => new EntityAlias('alias1', 'plural_alias1')
            ],
            $this->entityAliasResolver->getAll()
        );
    }

    public function testValidateDuplicatePluralAliasAndAlias()
    {
        $this->metadataFactory->expects($this->any())
            ->method('getAllMetadata')
            ->willReturn(
                [
                    new ClassMetadata('Test\Entity1'),
                    new ClassMetadata('Test\Entity2')
                ]
            );

        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->any())
            ->method('getEntityAlias')
            ->willReturnMap(
                [
                    ['Test\Entity1', new EntityAlias('alias1', 'plural_alias1')],
                    ['Test\Entity2', new EntityAlias('plural_alias1', 'plural_alias2')]
                ]
            );

        $this->entityAliasResolver->addProvider($entityAliasProvider);

        $this->setExpectedException(
            '\Oro\Bundle\EntityBundle\Exception\RuntimeException',
            'The alias "plural_alias1" cannot be used for the entity "Test\Entity2" because it is already '
            . 'used as a plural alias for the entity "Test\Entity1". To solve this problem you can use "entity_aliases"'
            . ' or "entity_alias_exclusions" section in the "Resources/config/oro/entity.yml" of your bundle or '
            . 'create a service to provide aliases for conflicting classes and register it '
            . 'with the tag "oro_entity.alias_provider" in DI container.'
        );

        $this->entityAliasResolver->getAll();
    }

    public function testValidateDuplicatePluralAliasAndAliasNoDebug()
    {
        $this->entityAliasResolver = new EntityAliasResolver($this->doctrine, false);

        $this->metadataFactory->expects($this->any())
            ->method('getAllMetadata')
            ->willReturn(
                [
                    new ClassMetadata('Test\Entity1'),
                    new ClassMetadata('Test\Entity2')
                ]
            );

        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->any())
            ->method('getEntityAlias')
            ->willReturnMap(
                [
                    ['Test\Entity1', new EntityAlias('alias1', 'plural_alias1')],
                    ['Test\Entity2', new EntityAlias('plural_alias1', 'plural_alias2')]
                ]
            );

        $this->entityAliasResolver->addProvider($entityAliasProvider);

        $this->assertEquals(
            [
                'Test\Entity1' => new EntityAlias('alias1', 'plural_alias1')
            ],
            $this->entityAliasResolver->getAll()
        );
    }

    public function testValidateDuplicateAliasesWithCustomHelpMessage()
    {
        $this->metadataFactory->expects($this->any())
            ->method('getAllMetadata')
            ->willReturn(
                [
                    new ClassMetadata('Test\Entity1'),
                    new ClassMetadata('Test\Entity2')
                ]
            );

        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->any())
            ->method('getEntityAlias')
            ->willReturnMap(
                [
                    ['Test\Entity1', new EntityAlias('alias', 'plural_alias1')],
                    ['Test\Entity2', new EntityAlias('alias', 'plural_alias2')]
                ]
            );

        $this->entityAliasResolver->addProvider($entityAliasProvider);

        $this->entityAliasResolver->setDuplicateAliasHelpMessage('CUSTOM HELP MESSAGE');

        $this->setExpectedException(
            '\Oro\Bundle\EntityBundle\Exception\RuntimeException',
            'The alias "alias" cannot be used for the entity "Test\Entity2" because it is already '
            . 'used for the entity "Test\Entity1". CUSTOM HELP MESSAGE'
        );

        $this->entityAliasResolver->getAll();
    }

    public function testThatEarlierProviderWins()
    {
        $this->metadataFactory->expects($this->any())
            ->method('getAllMetadata')
            ->willReturn([new ClassMetadata('Test\Entity1')]);

        $entityAliasProvider1 = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider1->expects($this->once())
            ->method('getEntityAlias')
            ->with('Test\Entity1')
            ->willReturn(new EntityAlias('alias1', 'plural_alias1'));
        $entityAliasProvider2 = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider2->expects($this->never())
            ->method('getEntityAlias');

        $this->entityAliasResolver->addProvider($entityAliasProvider1);
        $this->entityAliasResolver->addProvider($entityAliasProvider2);

        $this->assertEquals(
            [
                'Test\Entity1' => new EntityAlias('alias1', 'plural_alias1')
            ],
            $this->entityAliasResolver->getAll()
        );
    }

    public function testEntityAliasCanBeDisabled()
    {
        $this->metadataFactory->expects($this->any())
            ->method('getAllMetadata')
            ->willReturn([new ClassMetadata('Test\Entity1')]);

        $entityAliasProvider1 = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider1->expects($this->once())
            ->method('getEntityAlias')
            ->with('Test\Entity1')
            ->willReturn(false);
        $entityAliasProvider2 = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider2->expects($this->never())
            ->method('getEntityAlias');

        $this->entityAliasResolver->addProvider($entityAliasProvider1);
        $this->entityAliasResolver->addProvider($entityAliasProvider2);

        $this->assertSame(
            [],
            $this->entityAliasResolver->getAll()
        );
    }

    /**
     * @param int $expectedCallsOfGetAllMetadata
     */
    protected function initialiseAliases($expectedCallsOfGetAllMetadata)
    {
        $this->metadataFactory->expects($this->exactly($expectedCallsOfGetAllMetadata))
            ->method('getAllMetadata')
            ->willReturn(
                [
                    new ClassMetadata('Test\Entity1'),
                    new ClassMetadata('Test\Entity2')
                ]
            );

        $entityAliasProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface');
        $entityAliasProvider->expects($this->any())
            ->method('getEntityAlias')
            ->willReturnMap(
                [
                    ['Test\Entity1', new EntityAlias('entity1_alias', 'entity1_plural_alias')],
                    ['Test\Entity2', new EntityAlias('entity2_alias', 'entity2_plural_alias')]
                ]
            );

        $this->entityAliasResolver->addProvider($entityAliasProvider);
    }
}
