<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\FieldConfig;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityConfigTest extends \PHPUnit\Framework\TestCase
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
        $this->assertEquals([], $entityConfig->toArray());

        $entityConfig->setExcludeAll();

        $field = new FieldConfig();
        $field->setExcluded();
        $entityConfig->addField('test', $field);

        $this->assertEquals(
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
        $this->assertTrue($entityConfig->isEmpty());

        $entityConfig->setExcludeAll();
        $this->assertFalse($entityConfig->isEmpty());

        $entityConfig->setExcludeNone();
        $this->assertFalse($entityConfig->isEmpty());

        $entityConfig->setExclusionPolicy(null);
        $this->assertTrue($entityConfig->isEmpty());

        $entityConfig->addField('test');
        $this->assertFalse($entityConfig->isEmpty());

        $entityConfig->removeField('test');
        $this->assertTrue($entityConfig->isEmpty());
    }

    public function testFields(): void
    {
        $entityConfig = new EntityConfig();
        $this->assertFalse($entityConfig->hasField('test'));
        $this->assertNull($entityConfig->getField('test'));
        $this->assertEquals([], $entityConfig->getFields());

        $field = new FieldConfig();
        $this->assertSame($field, $entityConfig->addField('test', $field));
        $this->assertTrue($entityConfig->hasField('test'));
        $this->assertSame($field, $entityConfig->getField('test'));
        $this->assertEquals(['test' => $field], $entityConfig->getFields());
        $this->assertEquals(['fields' => ['test' => []]], $entityConfig->toArray());

        $entityConfig->removeField('test');
        $this->assertFalse($entityConfig->hasField('test'));
        $this->assertNull($entityConfig->getField('test'));
        $this->assertEquals([], $entityConfig->getFields());
        $this->assertEquals([], $entityConfig->toArray());
    }

    public function testExclusionPolicy(): void
    {
        $entityConfig = new EntityConfig();
        $this->assertFalse($entityConfig->isExcludeAll());
        $this->assertEquals([], $entityConfig->toArray());
        $this->assertTrue($entityConfig->isEmpty());

        $entityConfig->setExcludeAll();
        $this->assertTrue($entityConfig->isExcludeAll());
        $this->assertEquals(['exclusion_policy' => 'all'], $entityConfig->toArray());
        $this->assertFalse($entityConfig->isEmpty());

        $entityConfig->setExcludeNone();
        $this->assertFalse($entityConfig->isExcludeAll());
        $this->assertEquals([], $entityConfig->toArray());
        $this->assertFalse($entityConfig->isEmpty());

        $entityConfig->setExclusionPolicy('all');
        $this->assertTrue($entityConfig->isExcludeAll());
        $this->assertEquals(['exclusion_policy' => 'all'], $entityConfig->toArray());
        $this->assertFalse($entityConfig->isEmpty());

        $entityConfig->setExclusionPolicy('none');
        $this->assertFalse($entityConfig->isExcludeAll());
        $this->assertEquals([], $entityConfig->toArray());
        $this->assertFalse($entityConfig->isEmpty());

        $entityConfig->setExclusionPolicy(null);
        $this->assertFalse($entityConfig->isExcludeAll());
        $this->assertEquals([], $entityConfig->toArray());
        $this->assertTrue($entityConfig->isEmpty());
    }

    public function testPartialLoad(): void
    {
        $entityConfig = new EntityConfig();
        $this->assertTrue($entityConfig->isPartialLoadEnabled());

        $entityConfig->disablePartialLoad();
        $this->assertFalse($entityConfig->isPartialLoadEnabled());
        $this->assertEquals(['disable_partial_load' => true], $entityConfig->toArray());

        $entityConfig->enablePartialLoad();
        $this->assertTrue($entityConfig->isPartialLoadEnabled());
        $this->assertEquals([], $entityConfig->toArray());
    }

    public function testOrderBy(): void
    {
        $entityConfig = new EntityConfig();
        $this->assertEquals([], $entityConfig->getOrderBy());

        $entityConfig->setOrderBy(['test' => 'ASC']);
        $this->assertEquals(['test' => 'ASC'], $entityConfig->getOrderBy());
        $this->assertEquals(['order_by' => ['test' => 'ASC']], $entityConfig->toArray());

        $entityConfig->setOrderBy([]);
        $this->assertEquals([], $entityConfig->getOrderBy());
        $this->assertEquals([], $entityConfig->toArray());
    }

    public function testMaxResults(): void
    {
        $entityConfig = new EntityConfig();
        $this->assertNull($entityConfig->getMaxResults());

        $entityConfig->setMaxResults(123);
        $this->assertEquals(123, $entityConfig->getMaxResults());
        $this->assertEquals(['max_results' => 123], $entityConfig->toArray());

        $entityConfig->setMaxResults(null);
        $this->assertNull($entityConfig->getMaxResults());
        $this->assertEquals([], $entityConfig->toArray());
    }

    public function testHasMore(): void
    {
        $entityConfig = new EntityConfig();
        $this->assertFalse($entityConfig->getHasMore());

        $entityConfig->setHasMore(true);
        $this->assertTrue($entityConfig->getHasMore());
        $this->assertSame(['has_more' => true], $entityConfig->toArray());

        $entityConfig->setHasMore(false);
        $this->assertFalse($entityConfig->getHasMore());
        $this->assertSame([], $entityConfig->toArray());
    }

    public function testHints(): void
    {
        $entityConfig = new EntityConfig();
        $this->assertSame([], $entityConfig->getHints());

        $entityConfig->addHint('hint1');
        $entityConfig->addHint('hint2', 'val');
        $this->assertEquals(['hint1', ['name' => 'hint2', 'value' => 'val']], $entityConfig->getHints());
        $this->assertEquals(['hints' => ['hint1', ['name' => 'hint2', 'value' => 'val']]], $entityConfig->toArray());

        $entityConfig->removeHint('hint1', 'val');
        $this->assertEquals(['hint1', ['name' => 'hint2', 'value' => 'val']], $entityConfig->getHints());
        $this->assertEquals(['hints' => ['hint1', ['name' => 'hint2', 'value' => 'val']]], $entityConfig->toArray());

        $entityConfig->removeHint('hint1');
        $this->assertEquals([['name' => 'hint2', 'value' => 'val']], $entityConfig->getHints());
        $this->assertEquals(['hints' => [['name' => 'hint2', 'value' => 'val']]], $entityConfig->toArray());

        $entityConfig->removeHint('hint2');
        $this->assertEquals([['name' => 'hint2', 'value' => 'val']], $entityConfig->getHints());
        $this->assertEquals(['hints' => [['name' => 'hint2', 'value' => 'val']]], $entityConfig->toArray());

        $entityConfig->removeHint('hint2', 'val');
        $this->assertSame([], $entityConfig->getHints());
        $this->assertSame([], $entityConfig->toArray());
    }

    public function testInnerJoinAssociations(): void
    {
        $entityConfig = new EntityConfig();
        $this->assertSame([], $entityConfig->getInnerJoinAssociations());

        $entityConfig->addInnerJoinAssociation('association1');
        $this->assertEquals(['association1'], $entityConfig->getInnerJoinAssociations());
        $this->assertEquals(['inner_join_associations' => ['association1']], $entityConfig->toArray());

        $entityConfig->addInnerJoinAssociation('association2');
        $this->assertEquals(['association1', 'association2'], $entityConfig->getInnerJoinAssociations());
        $this->assertEquals(['inner_join_associations' => ['association1', 'association2']], $entityConfig->toArray());

        $entityConfig->addInnerJoinAssociation('association1');
        $this->assertEquals(['association1', 'association2'], $entityConfig->getInnerJoinAssociations());
        $this->assertEquals(['inner_join_associations' => ['association1', 'association2']], $entityConfig->toArray());

        $entityConfig->removeInnerJoinAssociation('association1');
        $this->assertEquals(['association2'], $entityConfig->getInnerJoinAssociations());
        $this->assertEquals(['inner_join_associations' => ['association2']], $entityConfig->toArray());

        $entityConfig->removeInnerJoinAssociation('association1');
        $this->assertEquals(['association2'], $entityConfig->getInnerJoinAssociations());
        $this->assertEquals(['inner_join_associations' => ['association2']], $entityConfig->toArray());

        $entityConfig->removeInnerJoinAssociation('association2');
        $this->assertSame([], $entityConfig->getInnerJoinAssociations());
        $this->assertSame([], $entityConfig->toArray());

        $entityConfig->setInnerJoinAssociations(['association1', 'association2']);
        $this->assertEquals(['association1', 'association2'], $entityConfig->getInnerJoinAssociations());
        $this->assertEquals(['inner_join_associations' => ['association1', 'association2']], $entityConfig->toArray());

        $entityConfig->setInnerJoinAssociations([]);
        $this->assertSame([], $entityConfig->getInnerJoinAssociations());
        $this->assertSame([], $entityConfig->toArray());
    }

    public function testPostSerializeHandler(): void
    {
        $entityConfig = new EntityConfig();
        $this->assertNull($entityConfig->getPostSerializeHandler());

        $handler = function (array $item, array $context) : array {
        };
        $entityConfig->setPostSerializeHandler($handler);
        $this->assertSame($handler, $entityConfig->getPostSerializeHandler());
        $this->assertEquals(['post_serialize' => $handler], $entityConfig->toArray());

        $entityConfig->setPostSerializeHandler(null);
        $this->assertNull($entityConfig->getPostSerializeHandler());
        $this->assertEquals([], $entityConfig->toArray());
    }

    public function testPostSerializeCollectionHandler(): void
    {
        $entityConfig = new EntityConfig();
        $this->assertNull($entityConfig->getPostSerializeCollectionHandler());

        $handler = function (array $items, array $context) : array {
        };
        $entityConfig->setPostSerializeCollectionHandler($handler);
        $this->assertSame($handler, $entityConfig->getPostSerializeCollectionHandler());
        $this->assertEquals(['post_serialize_collection' => $handler], $entityConfig->toArray());

        $entityConfig->setPostSerializeCollectionHandler(null);
        $this->assertNull($entityConfig->getPostSerializeCollectionHandler());
        $this->assertEquals([], $entityConfig->toArray());
    }
}
