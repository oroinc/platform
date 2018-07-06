<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Bundle\ApiBundle\DataTransformer\DateToStringTransformer;

class DateToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateToStringTransformer */
    private $transformer;

    protected function setUp()
    {
        $this->transformer = new DateToStringTransformer();
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
            ['1970-01-01', '1970-01-01 16:05:06 UTC']
        ];
    }
}
