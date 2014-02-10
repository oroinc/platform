<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Entity;

use Oro\Bundle\ImapBundle\Entity\ImapEmail;

class ImapEmailTest extends \PHPUnit_Framework_TestCase
{
    const TEST_STRING    = 'testString';
    const TEST_ID        = 123;
    const TEST_FLOAT     = 123.123;

    /** @var ImapEmail */
    protected $entity;

    public function setUp()
    {
        $this->entity = new ImapEmail();
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
        if (method_exists($this->entity, 'set' . ucfirst($property))) {
            if ($value !== null) {
                call_user_func_array(array($this->entity, 'set' . ucfirst($property)), array($value));
            }
        }

        $this->assertEquals($expected, call_user_func_array(array($this->entity, 'get' . ucfirst($property)), array()));
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email', array(), array(), '', false);
        return [
            'uid'        => ['uid', self::TEST_ID, self::TEST_ID],
            'email'        => ['email', $email, $email],
        ];
    }
}
