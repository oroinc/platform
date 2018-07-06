<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Bundle\ApiBundle\DataTransformer\DateTimeToStringTransformer;

class DateTimeToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateTimeToStringTransformer */
    private $transformer;

    protected function setUp()
    {
        $this->transformer = new DateTimeToStringTransformer();
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform($output, $input)
    {
        if (null === $input) {
            self::assertNull($output, $this->transformer->transform($input));
        } else {
            self::assertEquals($output, $this->transformer->transform(new \DateTime($input)));
        }
    }

    public function transformDataProvider()
    {
        return [
            [null, null],
            ['1970-01-01T16:05:06Z', '1970-01-01 16:05:06 UTC'],
            ['1970-01-01T16:05:06+02:00', '1970-01-01 16:05:06 +02:00']
        ];
    }
}
