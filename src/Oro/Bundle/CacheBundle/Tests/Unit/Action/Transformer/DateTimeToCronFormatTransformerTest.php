<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Action\Transformer;

use Oro\Bundle\CacheBundle\Action\Transformer\DateTimeToCronFormatTransformer;
use PHPUnit\Framework\TestCase;

class DateTimeToCronFormatTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transformer = new DateTimeToCronFormatTransformer();
        $cronDefinition = '05 15 01 09 *';
        $dateTime = \DateTime::createFromFormat('i H d m *', $cronDefinition, new \DateTimeZone('UTC'));

        self::assertSame($cronDefinition, $transformer->transform($dateTime));
    }

    public function testReverseTransform(): void
    {
        $transformer = new DateTimeToCronFormatTransformer();
        $cronDefinition = '05 15 01 09 *';
        $expected = \DateTime::createFromFormat('i H d m *', $cronDefinition, new \DateTimeZone('UTC'));

        self::assertEquals($expected, $transformer->reverseTransform($cronDefinition));
    }

    public function testReverseTransformWrongString(): void
    {
        $transformer = new DateTimeToCronFormatTransformer();
        $cronDefinition = '70 15 1';

        self::assertNull($transformer->reverseTransform($cronDefinition));
    }
}
