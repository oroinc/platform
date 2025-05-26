<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\FieldConfig;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityConfigTest extends TestCase
{
    public function testCustomAttribute(): void
    {
        $attrName = 'test';

        $entityConfig = new EntityConfig();
        self::assertFalse($entityConfig->has($attrName));
        self::assertNull($entityConfig->get($attrName));

        $entityConfig->set($attrName, null);
        self::assertTrue($entityConfig->has($attrName));
        self::assertNull($entityConfig->get($attrName));
        self::assertEquals([$attrName => null], $entityConfig->toArray());

        $entityConfig->set($attrName, false);
        self::assertTrue($entityConfig->has($attrName));
        self::assertFalse($entityConfig->get($attrName));
        self::assertEquals([$attrName => false], $entityConfig->toArray());

        $entityConfig->remove($attrName);
        self::assertFalse($entityConfig->has($attrName));
        self::assertNull($entityConfig->get($attrName));
        self::assertSame([], $entityConfig->toArray());
    }

    public function testToArray(): void
    {
        $entityConfig = new EntityConfig();
        self::assertEquals([], $entityConfig->toArray());

        $entityConfig->setExcludeAll();

        $field = new FieldConfig();
        $field->setExcluded();
        $entityConfig->addField('test', $field);

        self::assertEquals(
            [
                'exclusion_policy' => 'all',
                'fields'           => [
                    'test' => ['exclude' => true]
                ]
            ],
            $entityConfig->toArray()
        );
    }

    public function testIsEmpty(): void
    {
        $entityConfig = new EntityConfig();
        self::assertTrue($entityConfig->isEmpty());

        $entityConfig->setExcludeAll();
        self::assertFalse($entityConfig->isEmpty());

        $entityConfig->setExcludeNone();
        self::assertFalse($entityConfig->isEmpty());

        $entityConfig->setExclusionPolicy(null);
        self::assertTrue($entityConfig->isEmpty());

        $entityConfig->addField('test');
        self::assertFalse($entityConfig->isEmpty());

        $entityConfig->removeField('test');
        self::assertTrue($entityConfig->isEmpty());
    }

    public function testFields(): void
    {
        $entityConfig = new EntityConfig();
        self::assertFalse($entityConfig->hasField('test'));
        self::assertNull($entityConfig->getField('test'));
        self::assertEquals([], $entityConfig->getFields());

        $field = new FieldConfig();
        self::assertSame($field, $entityConfig->addField('test', $field));
        self::assertTrue($entityConfig->hasField('test'));
        self::assertSame($field, $entityConfig->getField('test'));
        self::assertEquals(['test' => $field], $entityConfig->getFields());
        self::assertEquals(['fields' => ['test' => []]], $entityConfig->toArray());

        $entityConfig->removeField('test');
        self::assertFalse($entityConfig->hasField('test'));
        self::assertNull($entityConfig->getField('test'));
        self::assertEquals([], $entityConfig->getFields());
        self::assertEquals([], $entityConfig->toArray());
    }

    public function testExclusionPolicy(): void
    {
        $entityConfig = new EntityConfig();
        self::assertFalse($entityConfig->isExcludeAll());
        self::assertEquals([], $entityConfig->toArray());
        self::assertTrue($entityConfig->isEmpty());

        $entityConfig->setExcludeAll();
        self::assertTrue($entityConfig->isExcludeAll());
        self::assertEquals(['exclusion_policy' => 'all'], $entityConfig->toArray());
        self::assertFalse($entityConfig->isEmpty());

        $entityConfig->setExcludeNone();
        self::assertFalse($entityConfig->isExcludeAll());
        self::assertEquals([], $entityConfig->toArray());
        self::assertFalse($entityConfig->isEmpty());

        $entityConfig->setExclusionPolicy('all');
        self::assertTrue($entityConfig->isExcludeAll());
        self::assertEquals(['exclusion_policy' => 'all'], $entityConfig->toArray());
        self::assertFalse($entityConfig->isEmpty());

        $entityConfig->setExclusionPolicy('none');
        self::assertFalse($entityConfig->isExcludeAll());
        self::assertEquals([], $entityConfig->toArray());
        self::assertFalse($entityConfig->isEmpty());

        $entityConfig->setExclusionPolicy(null);
        self::assertFalse($entityConfig->isExcludeAll());
        self::assertEquals([], $entityConfig->toArray());
        self::assertTrue($entityConfig->isEmpty());
    }

    public function testPartialLoad(): void
    {
        $entityConfig = new EntityConfig();
        self::assertTrue($entityConfig->isPartialLoadEnabled());

        $entityConfig->disablePartialLoad();
        self::assertFalse($entityConfig->isPartialLoadEnabled());
        self::assertEquals(['disable_partial_load' => true], $entityConfig->toArray());

        $entityConfig->enablePartialLoad();
        self::assertTrue($entityConfig->isPartialLoadEnabled());
        self::assertEquals([], $entityConfig->toArray());
    }

    public function testOrderBy(): void
    {
        $entityConfig = new EntityConfig();
        self::assertEquals([], $entityConfig->getOrderBy());

        $entityConfig->setOrderBy(['test' => 'ASC']);
        self::assertEquals(['test' => 'ASC'], $entityConfig->getOrderBy());
        self::assertEquals(['order_by' => ['test' => 'ASC']], $entityConfig->toArray());

        $entityConfig->setOrderBy([]);
        self::assertEquals([], $entityConfig->getOrderBy());
        self::assertEquals([], $entityConfig->toArray());
    }

    public function testMaxResults(): void
    {
        $entityConfig = new EntityConfig();
        self::assertNull($entityConfig->getMaxResults());

        $entityConfig->setMaxResults(123);
        self::assertEquals(123, $entityConfig->getMaxResults());
        self::assertEquals(['max_results' => 123], $entityConfig->toArray());

        $entityConfig->setMaxResults(null);
        self::assertNull($entityConfig->getMaxResults());
        self::assertEquals([], $entityConfig->toArray());
    }

    public function testHasMore(): void
    {
        $entityConfig = new EntityConfig();
        self::assertFalse($entityConfig->getHasMore());

        $entityConfig->setHasMore(true);
        self::assertTrue($entityConfig->getHasMore());
        self::assertSame(['has_more' => true], $entityConfig->toArray());

        $entityConfig->setHasMore(false);
        self::assertFalse($entityConfig->getHasMore());
        self::assertSame([], $entityConfig->toArray());
    }

    public function testHints(): void
    {
        $entityConfig = new EntityConfig();
        self::assertSame([], $entityConfig->getHints());

        $entityConfig->addHint('hint1');
        $entityConfig->addHint('hint2', 'val');
        self::assertEquals(['hint1', ['name' => 'hint2', 'value' => 'val']], $entityConfig->getHints());
        self::assertEquals(['hints' => ['hint1', ['name' => 'hint2', 'value' => 'val']]], $entityConfig->toArray());

        $entityConfig->removeHint('hint1', 'val');
        self::assertEquals(['hint1', ['name' => 'hint2', 'value' => 'val']], $entityConfig->getHints());
        self::assertEquals(['hints' => ['hint1', ['name' => 'hint2', 'value' => 'val']]], $entityConfig->toArray());

        $entityConfig->removeHint('hint1');
        self::assertEquals([['name' => 'hint2', 'value' => 'val']], $entityConfig->getHints());
        self::assertEquals(['hints' => [['name' => 'hint2', 'value' => 'val']]], $entityConfig->toArray());

        $entityConfig->removeHint('hint2');
        self::assertEquals([['name' => 'hint2', 'value' => 'val']], $entityConfig->getHints());
        self::assertEquals(['hints' => [['name' => 'hint2', 'value' => 'val']]], $entityConfig->toArray());

        $entityConfig->removeHint('hint2', 'val');
        self::assertSame([], $entityConfig->getHints());
        self::assertSame([], $entityConfig->toArray());
    }

    public function testInnerJoinAssociations(): void
    {
        $entityConfig = new EntityConfig();
        self::assertSame([], $entityConfig->getInnerJoinAssociations());

        $entityConfig->addInnerJoinAssociation('association1');
        self::assertEquals(['association1'], $entityConfig->getInnerJoinAssociations());
        self::assertEquals(['inner_join_associations' => ['association1']], $entityConfig->toArray());

        $entityConfig->addInnerJoinAssociation('association2');
        self::assertEquals(['association1', 'association2'], $entityConfig->getInnerJoinAssociations());
        self::assertEquals(['inner_join_associations' => ['association1', 'association2']], $entityConfig->toArray());

        $entityConfig->addInnerJoinAssociation('association1');
        self::assertEquals(['association1', 'association2'], $entityConfig->getInnerJoinAssociations());
        self::assertEquals(['inner_join_associations' => ['association1', 'association2']], $entityConfig->toArray());

        $entityConfig->removeInnerJoinAssociation('association1');
        self::assertEquals(['association2'], $entityConfig->getInnerJoinAssociations());
        self::assertEquals(['inner_join_associations' => ['association2']], $entityConfig->toArray());

        $entityConfig->removeInnerJoinAssociation('association1');
        self::assertEquals(['association2'], $entityConfig->getInnerJoinAssociations());
        self::assertEquals(['inner_join_associations' => ['association2']], $entityConfig->toArray());

        $entityConfig->removeInnerJoinAssociation('association2');
        self::assertSame([], $entityConfig->getInnerJoinAssociations());
        self::assertSame([], $entityConfig->toArray());

        $entityConfig->setInnerJoinAssociations(['association1', 'association2']);
        self::assertEquals(['association1', 'association2'], $entityConfig->getInnerJoinAssociations());
        self::assertEquals(['inner_join_associations' => ['association1', 'association2']], $entityConfig->toArray());

        $entityConfig->setInnerJoinAssociations([]);
        self::assertSame([], $entityConfig->getInnerJoinAssociations());
        self::assertSame([], $entityConfig->toArray());
    }

    public function testPostSerializeHandler(): void
    {
        $entityConfig = new EntityConfig();
        self::assertNull($entityConfig->getPostSerializeHandler());

        $handler = function (array $item, array $context): array {
        };
        $entityConfig->setPostSerializeHandler($handler);
        self::assertSame($handler, $entityConfig->getPostSerializeHandler());
        self::assertEquals(['post_serialize' => $handler], $entityConfig->toArray());

        $entityConfig->setPostSerializeHandler(null);
        self::assertNull($entityConfig->getPostSerializeHandler());
        self::assertEquals([], $entityConfig->toArray());
    }

    public function testPostSerializeCollectionHandler(): void
    {
        $entityConfig = new EntityConfig();
        self::assertNull($entityConfig->getPostSerializeCollectionHandler());

        $handler = function (array $items, array $context): array {
        };
        $entityConfig->setPostSerializeCollectionHandler($handler);
        self::assertSame($handler, $entityConfig->getPostSerializeCollectionHandler());
        self::assertEquals(['post_serialize_collection' => $handler], $entityConfig->toArray());

        $entityConfig->setPostSerializeCollectionHandler(null);
        self::assertNull($entityConfig->getPostSerializeCollectionHandler());
        self::assertEquals([], $entityConfig->toArray());
    }
}
