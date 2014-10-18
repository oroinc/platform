<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Tools;

use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\SomeEntity;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestCustomEntity;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestPhoneHolder;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestUser;
use Oro\Bundle\AddressBundle\Tools\PhoneHolderHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class PhoneHolderHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var PhoneHolderHelper */
    protected $helper;

    protected function setUp()
    {
        $this->extendConfigProvider = $this->getMock('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');

        $this->helper = new PhoneHolderHelper($this->extendConfigProvider);
    }

    /**
     * @dataProvider getPhoneNumberProvider
     */
    public function testGetPhoneNumber($object, $expected)
    {
        $this->assertEquals($expected, $this->helper->getPhoneNumber($object));
    }

    public function testGetPhoneNumberFromRelatedObjectNotConfigurableEntity()
    {
        $object = new TestCustomEntity();
        $object->setUser(new TestUser('user phone'));
        $object->setPhoneHolder(new TestPhoneHolder('123-123'));

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(get_class($object))
            ->will($this->returnValue(false));
        $this->extendConfigProvider->expects($this->never())
            ->method('getConfig');

        $this->assertEquals(null, $this->helper->getPhoneNumber($object));
    }

    public function testGetPhoneNumberFromRelatedObjectNoTargetEntities()
    {
        $object = new TestCustomEntity();
        $object->setUser(new TestUser('user phone'));
        $object->setPhoneHolder(new TestPhoneHolder('123-123'));
        $object->setOther(new SomeEntity());

        $config = new Config(new EntityConfigId('extend', get_class($object)));
        $config->set(
            'relation',
            [
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'user', 'manyToOne'),
                    'target_entity' => 'Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestUser'
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'phoneHolder', 'manyToOne'),
                    'target_entity' => 'Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestPhoneHolder'
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'other', 'manyToOne'),
                    'target_entity' => 'Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\SomeEntity'
                ],
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(get_class($object))
            ->will($this->returnValue(true));
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(get_class($object))
            ->will($this->returnValue($config));

        $this->assertEquals(null, $this->helper->getPhoneNumber($object));
    }

    public function testGetPhoneNumberFromRelatedObject()
    {
        $object = new TestCustomEntity();
        $object->setUser(new TestUser('user phone'));
        $object->setPhoneHolder(new TestPhoneHolder('123-123'));
        $object->setOther(new SomeEntity());

        $config = new Config(new EntityConfigId('extend', get_class($object)));
        $config->set(
            'relation',
            [
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'user', 'manyToOne'),
                    'target_entity' => 'Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestUser'
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'phoneHolder', 'manyToOne'),
                    'target_entity' => 'Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestPhoneHolder'
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'other', 'manyToOne'),
                    'target_entity' => 'Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\SomeEntity'
                ],
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(get_class($object))
            ->will($this->returnValue(true));
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(get_class($object))
            ->will($this->returnValue($config));

        $this->helper->addTargetEntity('Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestUser');
        $this->helper->addTargetEntity('Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestPhoneHolder', -10);

        $this->assertEquals('123-123', $this->helper->getPhoneNumber($object));
    }

    public function getPhoneNumberProvider()
    {
        return array(
            'null'                                => array(null, null),
            'not obj'                             => array(
                'Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestPhoneHolder',
                null
            ),
            'obj implements PhoneHolderInterface' => array(
                new TestPhoneHolder('123-123'),
                '123-123'
            ),
            'obj has getPhone method'             => array(
                new TestUser('123-123'),
                '123-123'
            ),
        );
    }
}
