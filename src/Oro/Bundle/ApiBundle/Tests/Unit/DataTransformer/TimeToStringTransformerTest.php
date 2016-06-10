<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Bundle\ApiBundle\DataTransformer\TimeToStringTransformer;

class TimeToStringTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var TimeToStringTransformer */
    protected $transformer;

    protected function setUp()
    {
        $this->transformer = new TimeToStringTransformer();
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform($output, $input)
    {
        if (null === $input) {
            $this->assertNull($output, $this->transformer->transform($input));
        } else {
            $this->assertEquals($output, $this->transformer->transform(new \DateTime($input)));
        }
    }

    public function transformDataProvider()
    {
        return [
            [null, null],
            ['16:05:06', '1970-01-01 16:05:06 UTC'],
        ];
    }
}
