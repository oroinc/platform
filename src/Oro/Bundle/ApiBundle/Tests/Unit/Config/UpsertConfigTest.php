<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\UpsertConfig;
use PHPUnit\Framework\TestCase;

class UpsertConfigTest extends TestCase
{
    public function testInitialState(): void
    {
        $config = new UpsertConfig();
        self::assertTrue($config->isEnabled());
        self::assertSame([], $config->getFields());
        self::assertSame([], $config->toArray());
    }

    public function testEnabled(): void
    {
        $config = new UpsertConfig();
        self::assertFalse($config->hasEnabled());
        self::assertTrue($config->isEnabled());
        self::assertSame([], $config->toArray());

        $config->setEnabled(false);
        self::assertTrue($config->hasEnabled());
        self::assertFalse($config->isEnabled());
        self::assertSame([], $config->toArray());

        $config->setEnabled(true);
        self::assertTrue($config->hasEnabled());
        self::assertTrue($config->isEnabled());
        self::assertSame([], $config->toArray());
    }

    public function testAllowedById(): void
    {
        $config = new UpsertConfig();
        self::assertFalse($config->hasAllowedById());
        self::assertFalse($config->isAllowedById());
        self::assertSame([], $config->toArray());

        $config->setAllowedById(false);
        self::assertTrue($config->hasAllowedById());
        self::assertFalse($config->isAllowedById());
        self::assertSame([], $config->toArray());

        $config->setAllowedById(true);
        self::assertTrue($config->hasAllowedById());
        self::assertTrue($config->isAllowedById());
        self::assertSame([['id']], $config->toArray());
    }

    public function testAddFields(): void
    {
        $config = new UpsertConfig();
        self::assertSame([], $config->getFields());
        self::assertSame([], $config->toArray());

        $config->addFields(['field1', 'field2']);
        self::assertSame([['field1', 'field2']], $config->getFields());
        self::assertSame([['field1', 'field2']], $config->toArray());

        $config->addFields(['field3', 'field1']);
        self::assertSame([['field1', 'field2'], ['field1', 'field3']], $config->getFields());
        self::assertSame([['field1', 'field2'], ['field1', 'field3']], $config->toArray());

        $config->addFields(['field1', 'field2']);
        self::assertSame([['field1', 'field2'], ['field1', 'field3']], $config->getFields());
        self::assertSame([['field1', 'field2'], ['field1', 'field3']], $config->toArray());

        $config->addFields(['field2', 'field1']);
        self::assertSame([['field1', 'field2'], ['field1', 'field3']], $config->getFields());
        self::assertSame([['field1', 'field2'], ['field1', 'field3']], $config->toArray());

        $config->addFields(['id']);
        self::assertSame([['field1', 'field2'], ['field1', 'field3']], $config->getFields());
        self::assertSame([['id'], ['field1', 'field2'], ['field1', 'field3']], $config->toArray());
        self::assertTrue($config->isAllowedById());

        self::assertFalse($config->isReplaceFields());
    }

    public function testRemoveFields(): void
    {
        $config = new UpsertConfig();
        $config->setAllowedById(true);
        $config->addFields(['field1', 'field2']);
        $config->addFields(['field3', 'field1']);
        self::assertSame([['id'], ['field1', 'field2'], ['field1', 'field3']], $config->toArray());

        $config->removeFields(['field1', 'field3']);
        self::assertSame([['field1', 'field2']], $config->getFields());
        self::assertSame([['id'], ['field1', 'field2']], $config->toArray());

        $config->removeFields(['id']);
        self::assertSame([['field1', 'field2']], $config->getFields());
        self::assertSame([['field1', 'field2']], $config->toArray());
        self::assertFalse($config->isAllowedById());

        self::assertFalse($config->isReplaceFields());
    }

    public function testReplaceFields(): void
    {
        $config = new UpsertConfig();
        $config->addFields(['field1', 'field2']);
        $config->addFields(['field3', 'field1']);
        self::assertSame([['field1', 'field2'], ['field1', 'field3']], $config->toArray());

        $config->replaceFields([['field1', 'field4']]);
        self::assertSame([['field1', 'field4']], $config->getFields());
        self::assertSame([['field1', 'field4']], $config->toArray());
        self::assertTrue($config->isReplaceFields());

        $config->addFields(['field1', 'field2']);
        self::assertSame([['field1', 'field4']], $config->getFields());
        self::assertSame([['field1', 'field4']], $config->toArray());
        self::assertTrue($config->isReplaceFields());

        $config->removeFields(['field1', 'field4']);
        self::assertSame([['field1', 'field4']], $config->getFields());
        self::assertSame([['field1', 'field4']], $config->toArray());
        self::assertTrue($config->isReplaceFields());

        $config->replaceFields([['field1', 'field3']]);
        self::assertSame([['field1', 'field3']], $config->getFields());
        self::assertSame([['field1', 'field3']], $config->toArray());
        self::assertTrue($config->isReplaceFields());

        $config->replaceFields([['field2', 'field3'], ['id']]);
        self::assertSame([['field2', 'field3']], $config->getFields());
        self::assertSame([['id'], ['field2', 'field3']], $config->toArray());
        self::assertTrue($config->isAllowedById());
        self::assertTrue($config->isReplaceFields());
    }

    public function testIsAllowedFields(): void
    {
        $config = new UpsertConfig();
        $config->addFields(['field1', 'field2']);
        $config->addFields(['field3', 'field1']);
        self::assertSame([['field1', 'field2'], ['field1', 'field3']], $config->toArray());

        self::assertTrue($config->isAllowedFields(['field1', 'field2']));
        self::assertTrue($config->isAllowedFields(['field3', 'field1']));
        self::assertTrue($config->isAllowedFields(['field1', 'field3']));
        self::assertFalse($config->isAllowedFields(['field1', 'field4']));
        self::assertFalse($config->isAllowedFields(['field1']));
    }
}
