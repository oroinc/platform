<?php

namespace Oro\Bundle\AddressBundle\Tests\Entity;

use Oro\Bundle\AddressBundle\Entity\AbstractEmail;

class AbstractEmailTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractEmail
     */
    protected $email;

    protected function setUp()
    {
        $this->email = $this->createEmail();
    }

    protected function tearDown()
    {
        unset($this->email);
    }

    public function testConstructor()
    {
        $this->email = $this->createEmail('email@example.com');

        $this->assertEquals('email@example.com', $this->email->getEmail());
    }

    public function testId()
    {
        $this->assertNull($this->email->getId());
        $this->email->setId(100);
        $this->assertEquals(100, $this->email->getId());
    }

    public function testEmail()
    {
        $this->assertNull($this->email->getEmail());
        $this->email->setEmail('email@example.com');
        $this->assertEquals('email@example.com', $this->email->getEmail());
    }

    public function testToString()
    {
        $this->assertEquals('', (string)$this->email);
        $this->email->setEmail('email@example.com');
        $this->assertEquals('email@example.com', (string)$this->email);
    }

    public function testPrimary()
    {
        $this->assertFalse($this->email->isPrimary());
        $this->email->setPrimary(true);
        $this->assertTrue($this->email->isPrimary());
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->createEmail()->isEmpty());
        $this->assertFalse($this->createEmail('foo@example.com')->isEmpty());
    }

    /**
     * @dataProvider isEqualDataProvider
     *
     * @param AbstractEmail $first
     * @param mixed $second
     * @param bool $expectedResult
     */
    public function testIsEqual(AbstractEmail $first, $second, $expectedResult)
    {
        $this->assertEquals($expectedResult, $first->isEqual($second));
    }

    /**
     * @return array
     */
    public function isEqualDataProvider()
    {
        $emailEmpty   = $this->createEmail();
        $emailAddress = $this->createEmail('a@a.a');

        return array(
            'both empty'           => array($emailEmpty, $emailEmpty, true),
            'one empty one unset'  => array($emailEmpty, null, false),
            'one empty'            => array($this->createEmail(100), $emailEmpty, false),
            'both with same id'    => array($this->createEmail('a@a.a', 100), $this->createEmail('b@b.b', 100), true),
            'equals not empty'     => array($emailAddress, $emailAddress, true),
            'not equals not empty' => array($emailAddress, $this->createEmail('b@b.b'), false),
        );
    }

    /**
     * @param string|null $email
     * @param int $id
     * @return AbstractEmail|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEmail($email = null, $id = null)
    {
        $arguments = array();

        if ($email) {
            $arguments[] = $email;
        }

        /** @var AbstractEmail $email */
        $email = $this->getMockForAbstractClass('Oro\Bundle\AddressBundle\Entity\AbstractEmail', $arguments);

        if ($id) {
            $email->setId($id);
        }

        return $email;
    }
}
