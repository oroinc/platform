<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\BusinessEntitiesBundle\Entity\BasePerson;

class BasePersonTest extends \PHPUnit_Framework_TestCase
{
    const TEST_STRING = 'testString';
    const TEST_ID     = 123;

    /** @var BasePerson */
    protected $entity;

    public function setUp()
    {
        $this->entity = new BasePerson();
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
            call_user_func_array(array($this->entity, 'set' . ucfirst($property)), array($value));
        }

        $this->assertEquals($expected, call_user_func_array(array($this->entity, 'get' . ucfirst($property)), array()));
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        $birthday = new \DateTime('now');
        $created  = new \DateTime('now');
        $updated  = new \DateTime('now');

        return [
            'id'         => ['id', self::TEST_ID, self::TEST_ID],
            'namePrefix' => ['namePrefix', self::TEST_STRING . 'prefix', self::TEST_STRING . 'prefix'],
            'firstName'  => ['firstName', self::TEST_STRING . 'first', self::TEST_STRING . 'first'],
            'middleName' => ['middleName', self::TEST_STRING . 'middle', self::TEST_STRING . 'middle'],
            'lastName'   => ['lastName', self::TEST_STRING . 'last', self::TEST_STRING . 'last'],
            'nameSuffix' => ['nameSuffix', self::TEST_STRING . 'suffix', self::TEST_STRING . 'suffix'],
            'gender'     => ['gender', 'male', 'male'],
            'birthday'   => ['birthday', $birthday, $birthday],
            'email'      => ['email', self::TEST_STRING . 'email', self::TEST_STRING . 'email'],
            'createdAt'  => ['createdAt', $created, $created],
            'updatedAt'  => ['updatedAt', $updated, $updated],
        ];
    }

    public function testSetGetAddress()
    {
        /** @var AbstractAddress $address */
        $address = $this->getMockForAbstractClass('Oro\Bundle\AddressBundle\Entity\AbstractAddress');
        $this->entity->addAddress($address);
        $this->assertAttributeEquals($this->entity->getAddresses(), 'addresses', $this->entity);
    }

    public function testHasAddress()
    {
        /** @var AbstractAddress $address */
        $address = $this->getMockForAbstractClass('Oro\Bundle\AddressBundle\Entity\AbstractAddress');
        $this->entity->addAddress($address);
        $this->assertTrue($this->entity->hasAddress($address));
    }

    public function testResetGetAddresses()
    {
        /** @var ArrayCollection $address */
        $addresses = new ArrayCollection(
            array(
                $this->getMockForAbstractClass('Oro\Bundle\AddressBundle\Entity\AbstractAddress'),
                $this->getMockForAbstractClass('Oro\Bundle\AddressBundle\Entity\AbstractAddress')
            )
        );
        $this->entity->resetAddresses($addresses);
        $this->assertEquals($addresses, $this->entity->getAddresses());
    }

    public function testRemoveAddresses()
    {
        /** @var ArrayCollection $address */
        $addresses = new ArrayCollection(
            array(
                $this->getMockForAbstractClass('Oro\Bundle\AddressBundle\Entity\AbstractAddress'),
                $this->getMockForAbstractClass('Oro\Bundle\AddressBundle\Entity\AbstractAddress')
            )
        );
        $this->entity->resetAddresses($addresses);
        $this->entity->removeAddress($addresses->first());
        $this->assertFalse($this->entity->hasAddress($addresses->first()));
        $this->assertEquals(1, $this->entity->getAddresses()->count());
    }

    public function testCloneAddresses()
    {
        /** @var ArrayCollection $address */
        $addresses = new ArrayCollection(
            array(
                $this->getMockForAbstractClass('Oro\Bundle\AddressBundle\Entity\AbstractAddress'),
                $this->getMockForAbstractClass('Oro\Bundle\AddressBundle\Entity\AbstractAddress')
            )
        );
        $this->entity->resetAddresses($addresses);

        $newEntity = clone $this->entity;
        $this->assertEquals($this->entity, $newEntity);
    }
}
