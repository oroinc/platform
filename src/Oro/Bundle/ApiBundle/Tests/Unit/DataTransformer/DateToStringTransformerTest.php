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

    public function testTransformNull()
    {
        self::assertNull($this->transformer->transform(null));
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform($output, $input)
    {
        self::assertEquals($output, $this->transformer->transform(new \DateTime($input)));
    }

    public function transformDataProvider()
    {
        return [
            ['1970-01-01', '1970-01-01 16:05:06 UTC']
        ];
    }
}
