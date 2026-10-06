<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\Action;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\ActionBundle\Model\Assembler\ActionGroupAssembler;
use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\EntityExtendBundle\Tests\Functional\DataFixtures\LoadTestEntityFieldsWithExtendData;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityFields;
use Oro\Bundle\TestFrameworkBundle\Entity\TestExtendedEntity;

/**
 * Functional test for the @duplicate action covering all filter types:
 * setNull, keep, replaceValue, shallowCopy, collection, emptyCollection.
 *
 * @dbIsolationPerTest
 */
class DuplicateExtendedEntityFieldsTest extends ActionTestCase
{
    private const ALL_FILTER_TYPES_SETTINGS = [
        [['setNull'], ['propertyName', ['id']]],
        [['keep'], ['propertyName', ['stringField']]],
        [['replaceValue', [999]], ['propertyName', ['integerField']]],
        [['shallowCopy'], ['propertyName', ['manyToOneRelation']]],
        [['keep'], ['propertyName', ['enum_field']]],
        [['emptyCollection'], ['propertyName', ['multienum_field']]],
        [['collection'], ['propertyType', [Collection::class]]],
    ];

    /** Rules for the declared fields only; the extended storage gets no rule unless a test adds one. */
    private const BASE_SETTINGS = [
        [['setNull'], ['propertyName', ['id']]],
        [['collection'], ['propertyName', ['manyToManyRelation']]],
    ];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient();
        $this->loadFixtures([LoadTestEntityFieldsWithExtendData::class]);
        $this->registerDuplicateActionGroup('test_entity_fields_duplicate', self::ALL_FILTER_TYPES_SETTINGS);
    }

    public function testAllFilterTypesAppliedToRegularAndExtendedFields(): void
    {
        /** @var TestEntityFields $original */
        $original = $this->getReference(LoadTestEntityFieldsWithExtendData::ENTITY);

        self::assertNotNull($original->getId(), 'Original must be persisted with an id');
        self::assertSame('original', $original->getStringField());
        self::assertSame(100, $original->getIntegerField());
        self::assertNotNull($original->getManyToOneRelation(), 'M2O relation must be set');
        self::assertCount(1, $original->getManyToManyRelation(), 'M2M must contain exactly one item');
        self::assertNotNull($original->get('enum_field'), 'Extended enum_field must be set before duplication');

        $actionData = $this->executeActionGroup('test_entity_fields_duplicate', [
            'entity' => $original,
        ]);

        /** @var TestEntityFields $copy */
        $copy = $actionData->offsetGet('entityCopy');
        self::assertInstanceOf(TestEntityFields::class, $copy);

        // setNull on regular field: id is nulled
        self::assertNull($copy->getId(), 'setNull must null the id');

        // keep on regular field: string preserved as-is
        self::assertSame('original', $copy->getStringField(), 'keep must preserve stringField value');

        // replaceValue on regular field: int overwritten with 999
        self::assertSame(999, $copy->getIntegerField(), 'replaceValue must override integerField');

        // shallowCopy on regular M2O: new PHP object with the same id
        $originalM2O = $original->getManyToOneRelation();
        $copyM2O = $copy->getManyToOneRelation();
        self::assertNotSame(
            $originalM2O,
            $copyM2O,
            'shallowCopy must produce a distinct PHP object for manyToOneRelation'
        );
        self::assertSame(
            $originalM2O->getId(),
            $copyM2O->getId(),
            'shallowCopy must preserve the related entity id'
        );

        // collection on regular M2M (via propertyType): elements deep-copied
        self::assertCount(
            1,
            $copy->getManyToManyRelation(),
            'collection filter must preserve the M2M element count'
        );

        // keep on extended field (via StorageFilter + SerializedFieldFilter)
        self::assertNotEmpty(
            $copy->get('enum_field'),
            'keep must not null the extended enum_field via SerializedFieldFilter'
        );

        // emptyCollection on extended collection (via StorageFilter)
        // Even if multienum_field was null on the original the filter must install
        // an explicit empty ArrayCollection on the copy (not null).
        $multiEnumCopy = $copy->get('multienum_field');
        self::assertInstanceOf(
            Collection::class,
            $multiEnumCopy,
            'emptyCollection must set multienum_field to a Collection instance (not null)'
        );
        self::assertCount(
            0,
            $multiEnumCopy,
            'emptyCollection must empty the extended multienum_field'
        );
    }

    public function testPropertyRuleAppliesToExtendedField(): void
    {
        $original = $this->getReference(LoadTestEntityFieldsWithExtendData::ENTITY);
        $originalEnum = $original->get('enum_field');

        $copy = $this->duplicate('property_rule', [
            ...self::BASE_SETTINGS,
            [['setNull'], ['property', [TestEntityFields::class, 'enum_field']]],
        ]);

        self::assertNull($copy->get('enum_field'), 'a property rule must apply to an extended field');
        self::assertSame($originalEnum, $original->get('enum_field'));
    }

    public function testPropertyRuleOfAnotherClassDoesNotApplyToExtendedField(): void
    {
        $original = $this->getReference(LoadTestEntityFieldsWithExtendData::ENTITY);

        $copy = $this->duplicate('foreign_property_rule', [
            ...self::BASE_SETTINGS,
            [['setNull'], ['property', [TestExtendedEntity::class, 'enum_field']]],
        ]);

        self::assertSame($original->get('enum_field'), $copy->get('enum_field'));
    }

    public function testSerializedEnumValueOfCopyIsManagedInstance(): void
    {
        $original = $this->getReference(LoadTestEntityFieldsWithExtendData::ENTITY);
        $enumOption = $this->getReference(LoadTestEntityFieldsWithExtendData::ENUM_OPTION);

        $copy = $this->duplicate('no_extend_rules', self::BASE_SETTINGS);

        self::assertSame($enumOption, $copy->get('enum_field'));
        self::assertSame($original->get('enum_field'), $copy->get('enum_field'));
        self::assertTrue(self::getContainer()->get('doctrine')->getManager()->contains($copy->get('enum_field')));
    }

    public function testNestedExtendEntityKeepsStorageObjectsByReference(): void
    {
        $original = $this->getReference(LoadTestEntityFieldsWithExtendData::ENTITY);
        /** @var TestExtendedEntity $originalRelated */
        $originalRelated = $original->getManyToOneRelation();
        $bagTarget = $this->getReference(LoadTestEntityFieldsWithExtendData::RELATED_BAG_TARGET);
        $relatedEnumOption = $this->getReference(LoadTestEntityFieldsWithExtendData::RELATED_ENUM_OPTION);
        self::assertInstanceOf(Collection::class, $originalRelated->get('biM2MOwners'));

        // no rule for manyToOneRelation: the related extend entity is deep-copied
        $copy = $this->duplicate('nested_extend_entity', self::BASE_SETTINGS);

        /** @var TestExtendedEntity $copyRelated */
        $copyRelated = $copy->getManyToOneRelation();
        self::assertNotSame($originalRelated, $copyRelated);
        self::assertSame($bagTarget, $copyRelated->get('oro_test_framework_test_entity_fields'));
        self::assertSame($originalRelated->get('biM2MOwners'), $copyRelated->get('biM2MOwners'));
        // serialized values of the nested entity are resolved with its own class
        self::assertSame('nested-serialized', $copyRelated->get('serialized_attribute'));
        self::assertSame($relatedEnumOption, $copyRelated->get('testExtendedEntityEnumAttribute'));
    }

    public function testCollectionRuleCopiesExtendStorageCollections(): void
    {
        $original = $this->getReference(LoadTestEntityFieldsWithExtendData::ENTITY);
        $originalCollection = $original->getManyToOneRelation()->get('biM2MOwners');

        $copy = $this->duplicate('collection_by_type', [
            [['setNull'], ['propertyName', ['id']]],
            [['collection'], ['propertyType', [Collection::class]]],
        ]);

        $copyCollection = $copy->getManyToOneRelation()->get('biM2MOwners');
        self::assertInstanceOf(Collection::class, $copyCollection);
        self::assertNotSame($originalCollection, $copyCollection);
        self::assertCount($originalCollection->count(), $copyCollection);
    }

    private function duplicate(string $name, array $settings): TestEntityFields
    {
        $this->registerDuplicateActionGroup($name, $settings);
        $actionData = $this->executeActionGroup($name, [
            'entity' => $this->getReference(LoadTestEntityFieldsWithExtendData::ENTITY),
        ]);
        $copy = $actionData->offsetGet('entityCopy');
        self::assertInstanceOf(TestEntityFields::class, $copy);

        return $copy;
    }

    private function registerDuplicateActionGroup(string $name, array $settings): void
    {
        $config = [
            $name => [
                'parameters' => [
                    'entity' => ['type' => TestEntityFields::class],
                ],
                'actions' => [
                    [
                        '@duplicate' => [
                            'target'    => '$.entity',
                            'attribute' => '$.entityCopy',
                            'settings'  => $settings,
                        ],
                    ],
                ],
            ],
        ];

        /** @var ActionGroupAssembler $assembler */
        $assembler = $this->getContainer()->get('oro_action.assembler.action_group');
        $groups = $assembler->assemble($config);

        /** @var ActionGroupRegistry $registry */
        $registry = $this->getContainer()->get('oro_action.action_group_registry');
        // Trigger lazy-loading of existing groups before injecting the test group
        $registry->findByName('__warmup__');
        $prop = new \ReflectionProperty($registry, 'actionGroups');
        $existing = $prop->getValue($registry) ?? [];
        $prop->setValue($registry, array_merge($existing, $groups));
    }
}
