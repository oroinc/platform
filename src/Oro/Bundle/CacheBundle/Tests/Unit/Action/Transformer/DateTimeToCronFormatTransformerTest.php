<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Action\Transformer;

use Oro\Bundle\CacheBundle\Action\Transformer\DateTimeToCronFormatTransformer;

class DateTimeToCronFormatTransformerTest extends \PHPUnit\Framework\TestCase
{
    public function testTransform()
    {
        $transformer = new DateTimeToCronFormatTransformer();
        $cronDefinition = '05 15 01 09 *';
        $dateTime = \DateTime::createFromFormat('i H d m *', $cronDefinition, new \DateTimeZone('UTC'));

        static::assertSame($cronDefinition, $transformer->transform($dateTime));
    }

    public function testReverseTransform()
    {
        $transformer = new DateTimeToCronFormatTransformer();
        $cronDefinition = '05 15 01 09 *';
        $expected = \DateTime::createFromFormat('i H d m *', $cronDefinition, new \DateTimeZone('UTC'));

        static::assertEquals($expected, $transformer->reverseTransform($cronDefinition));
    }

    public function testReverseTransformWrongString()
    {
        $transformer = new DateTimeToCronFormatTransformer();
        $cronDefinition = '70 15 1';

        static::assertNull($transformer->reverseTransform($cronDefinition));
    }
}
