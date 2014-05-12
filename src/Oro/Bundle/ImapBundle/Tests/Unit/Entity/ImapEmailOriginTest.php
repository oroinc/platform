<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Entity;

use Oro\Bundle\ImapBundle\Entity\ImapEmailOrigin;

class ImapEmailOriginTest extends \PHPUnit_Framework_TestCase
{
    const TEST_STRING    = 'testString';
    const TEST_ID        = 123;
    const TEST_FLOAT     = 123.123;

    /** @var ImapEmailOrigin */
    protected $entity;

    public function setUp()
    {
        $this->entity = new ImapEmailOrigin();
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
            call_user_func_array(array($this->entity, 'set' . ucfirst($property)), array($value));
        }

        $this->assertEquals($expected, call_user_func_array(array($this->entity, 'get' . ucfirst($property)), array()));
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        return [
            'host'        => ['host', self::TEST_STRING, self::TEST_STRING],
            'port'        => ['port', self::TEST_ID, self::TEST_ID],
            'ssl'         => ['ssl', self::TEST_STRING, self::TEST_STRING],
            'user'        => ['user', self::TEST_STRING, self::TEST_STRING],
            'password'    => ['password', self::TEST_STRING, self::TEST_STRING],
        ];
    }
}
