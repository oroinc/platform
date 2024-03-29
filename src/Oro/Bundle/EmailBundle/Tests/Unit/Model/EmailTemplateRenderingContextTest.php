<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Model\EmailTemplateRenderingContext;
use PHPUnit\Framework\TestCase;

class EmailTemplateRenderingContextTest extends TestCase
{
    public function testFillFromArray(): void
    {
        $items = ['key1' => 'value1', 'key2' => 'value2'];

        $context = new EmailTemplateRenderingContext();

        self::assertEquals([], $context->toArray());

        $context->fillFromArray($items);

        self::assertEquals($items, $context->toArray());
        self::assertEquals($items['key1'], $context->get('key1'));
        self::assertEquals($items['key2'], $context->get('key2'));
    }

    public function testFillFromArrayWhenNoEmpty(): void
    {
        $items = ['key1' => 'value1', 'key2' => 'value2'];

        $context = new EmailTemplateRenderingContext();
        $context->set('key1', 'existing_value1');

        self::assertEquals(['key1' => 'existing_value1'], $context->toArray());
        self::assertEquals('existing_value1', $context->get('key1'));

        $context->fillFromArray($items);

        self::assertEquals($items, $context->toArray());
        self::assertEquals($items['key1'], $context->get('key1'));
        self::assertEquals($items['key2'], $context->get('key2'));
    }

    public function testReset(): void
    {
        $context = new EmailTemplateRenderingContext();
        $context->set('key1', 'existing_value1');

        self::assertEquals(['key1' => 'existing_value1'], $context->toArray());

        $context->reset();
        self::assertEquals([], $context->toArray());
    }
}
