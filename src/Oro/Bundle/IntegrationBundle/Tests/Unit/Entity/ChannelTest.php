<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ChannelTest extends \PHPUnit_Framework_TestCase
{
    const TEST_STRING = 'testString';

    /** @var array */
    protected static $testConnectors = ['customer', 'product'];

    /** @var Channel */
    protected $entity;

    public function setUp()
    {
        $this->entity = new Channel();
    }

    public function tearDown()
    {
        unset($this->entity);
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
            'id'         => ['id'],
            'name'       => ['name', self::TEST_STRING, self::TEST_STRING],
            'type'       => ['type', self::TEST_STRING, self::TEST_STRING],
            'connectors' => ['connectors', self::$testConnectors, self::$testConnectors]
        ];
    }

    public function testTransportRelation()
    {
        $transport = $this->getMockForAbstractClass('Oro\Bundle\IntegrationBundle\Entity\Transport');
        $this->assertAttributeEmpty('transport', $this->entity);

        $this->entity->setTransport($transport);
        $this->assertSame($transport, $this->entity->getTransport());

        $this->entity->clearTransport();
        $this->assertAttributeEmpty('transport', $this->entity);
    }
}
