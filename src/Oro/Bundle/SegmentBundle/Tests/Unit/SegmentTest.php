<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Entity;

use Oro\Bundle\SegmentBundle\Entity\Segment;

class SegmentTest extends \PHPUnit_Framework_TestCase
{
    /** @var Segment */
    protected $entity;

    public function setUp()
    {
        $this->entity = new Segment();
    }

    /**
     * @dataProvider  getSetDataProvider
     *
     * @param string $property
     * @param mixed  $value
     * @param mixed  $expected
     */
    public function testSetGet($property, $value = null, $expected = null)
    {
        if ($value !== null) {
            call_user_func_array([$this->entity, 'set' . ucfirst($property)], [$value]);
        }

        $this->assertEquals($expected, call_user_func_array([$this->entity, 'get' . ucfirst($property)], []));
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        return [
            'name'        => ['id', 1, 1],
            'name'        => ['name', 'test', 'test'],
            'definition'  => ['definition', json_encode(['test' => 'test']), json_encode(['test' => 'test'])],
        ];
    }
}
