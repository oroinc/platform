<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\BusinessEntitiesBundle\Entity\BasePerson;

class BasePersonTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_STRING = 'testString';
    private const TEST_ID = 123;

    private BasePerson $entity;

    protected function setUp(): void
    {
        $this->entity = new BasePerson();
    }

    /**
     * @dataProvider getSetDataProvider
     */
    public function testSetGet(string $property, mixed $value = null, mixed $expected = null)
    {
        if ($value !== null) {
            call_user_func([$this->entity, 'set' . ucfirst($property)], $value);
        }

        $this->assertEquals($expected, call_user_func_array([$this->entity, 'get' . ucfirst($property)], []));
    }

    public function getSetDataProvider(): array
    {
        $birthday = new \DateTime('now');
        $created = new \DateTime('now');
        $updated = new \DateTime('now');

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

    public function testHasAddress()
    {
        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $this->entity->addAddress($address);
        $this->assertTrue($this->entity->hasAddress($address));
    }

    public function testResetGetAddresses()
    {
        $addresses = new ArrayCollection([
            $this->getMockForAbstractClass(AbstractAddress::class),
            $this->getMockForAbstractClass(AbstractAddress::class)
        ]);
        $this->entity->resetAddresses($addresses);
        $this->assertEquals($addresses, $this->entity->getAddresses());
    }

    public function testRemoveAddresses()
    {
        $addresses = new ArrayCollection([
            $this->getMockForAbstractClass(AbstractAddress::class),
            $this->getMockForAbstractClass(AbstractAddress::class)
        ]);
        $this->entity->resetAddresses($addresses);
        $this->entity->removeAddress($addresses->first());
        $this->assertFalse($this->entity->hasAddress($addresses->first()));
        $this->assertEquals(1, $this->entity->getAddresses()->count());
    }

    public function testCloneAddresses()
    {
        $addresses = new ArrayCollection([
            $this->getMockForAbstractClass(AbstractAddress::class),
            $this->getMockForAbstractClass(AbstractAddress::class)
        ]);
        $this->entity->resetAddresses($addresses);

        $newEntity = clone $this->entity;
        $this->assertEquals($this->entity, $newEntity);
    }
}
