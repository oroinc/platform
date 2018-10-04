<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Bundle\ApiBundle\DataTransformer\TimeToStringTransformer;

class TimeToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TimeToStringTransformer */
    private $transformer;

    protected function setUp()
    {
        $this->transformer = new TimeToStringTransformer();
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
            ['16:05:06', '1970-01-01 16:05:06 UTC']
        ];
    }
}
