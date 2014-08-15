<?php

namespace Oro\Bundle\AddressBundle\Tests\Entity;

use Oro\Bundle\AddressBundle\Entity\AbstractPhone;

class AbstractPhoneTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractPhone
     */
    protected $phone;

    protected function setUp()
    {
        $this->phone = $this->createPhone();
    }

    protected function tearDown()
    {
        unset($this->phone);
    }

    public function testConstructor()
    {
        $this->phone = $this->createPhone('080011223355');

        $this->assertEquals('080011223355', $this->phone->getPhone());
    }

    public function testId()
    {
        $this->assertNull($this->phone->getId());
        $this->phone->setId(100);
        $this->assertEquals(100, $this->phone->getId());
    }

    public function testPhone()
    {
        $this->assertNull($this->phone->getPhone());
        $this->phone->setPhone('080011223355');
        $this->assertEquals('080011223355', $this->phone->getPhone());
    }

    public function testToString()
    {
        $this->assertEquals('', (string)$this->phone);
        $this->phone->setPhone('080011223355');
        $this->assertEquals('080011223355', (string)$this->phone);
    }

    public function testPrimary()
    {
        $this->assertFalse($this->phone->isPrimary());
        $this->phone->setPrimary(true);
        $this->assertTrue($this->phone->isPrimary());
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->createPhone()->isEmpty());
        $this->assertFalse($this->createPhone('00110011')->isEmpty());
    }

    /**
     * @dataProvider isEqualDataProvider
     *
     * @param AbstractPhone $first
     * @param mixed $second
     * @param bool $expectedResult
     */
    public function testIsEqual(AbstractPhone $first, $second, $expectedResult)
    {
        $this->assertEquals($expectedResult, $first->isEqual($second));
    }

    /**
     * @return array
     */
    public function isEqualDataProvider()
    {
        $phoneEmpty  = $this->createPhone();
        $phoneSimple = $this->createPhone('123');

        return array(
            'both empty'           => array($phoneEmpty, $phoneEmpty, true),
            'one empty'            => array($this->createPhone(100), $phoneEmpty, false),
            'one empty one unset'  => array($phoneEmpty, null, false),
            'both with same id'    => array($this->createPhone('123', 100), $this->createPhone('789', 100), true),
            'equals not empty'     => array($phoneSimple, $phoneSimple, true),
            'not equals not empty' => array($phoneSimple, $this->createPhone('789'), false),
        );
    }


    /**
     * @param string|null $phone
     * @param int $id
     * @return AbstractPhone|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createPhone($phone = null, $id = null)
    {
        $arguments = array();

        if ($phone) {
            $arguments[] = $phone;
        }

        /** @var AbstractPhone $phone */
        $phone = $this->getMockForAbstractClass('Oro\Bundle\AddressBundle\Entity\AbstractPhone', $arguments);

        if ($id) {
            $phone->setId($id);
        }

        return $phone;
    }
}
