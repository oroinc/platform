<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Tools;

use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\SomeEntity;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestCustomEntity;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestPhoneHolder;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestUser;
use Oro\Bundle\AddressBundle\Provider\PhoneProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class PhoneProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var PhoneProvider */
    protected $provider;

    protected function setUp()
    {
        $this->extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new PhoneProvider($this->extendConfigProvider);
    }

    /**
     * @dataProvider getPhoneNumberProvider
     */
    public function testGetPhoneNumber($object, $expected)
    {
        $testPhoneHolderProvider = $this->getMock('Oro\Bundle\AddressBundle\Provider\PhoneProviderInterface');
        $testPhoneHolderProvider->expects($this->any())
            ->method('getPhoneNumber')
            ->will(
                $this->returnCallback(
                    function ($object) {
                        /** @var TestPhoneHolder $object */
                        return $object->getPhoneNumber();
                    }
                )
            );
        $this->provider->addPhoneProvider(
            'Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestPhoneHolder',
            $testPhoneHolderProvider
        );

        $this->assertEquals($expected, $this->provider->getPhoneNumber($object));
    }

    /**
     * @dataProvider getPhoneNumbersProvider
     */
    public function testGetPhoneNumbers($object, $expected)
    {
        $testPhoneHolderProvider = $this->getMock('Oro\Bundle\AddressBundle\Provider\PhoneProviderInterface');
        $testPhoneHolderProvider->expects($this->any())
            ->method('getPhoneNumbers')
            ->will(
                $this->returnCallback(
                    function ($object) {
                        /** @var TestPhoneHolder $object */
                        return $object->getPhoneNumbers();
                    }
                )
            );
        $this->provider->addPhoneProvider(
            'Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestPhoneHolder',
            $testPhoneHolderProvider
        );

        $this->assertSame($expected, $this->provider->getPhoneNumbers($object));
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

        $this->assertEquals(null, $this->provider->getPhoneNumber($object));
    }

    public function testGetPhoneNumbersFromRelatedObjectNotConfigurableEntity()
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

        $this->assertSame([], $this->provider->getPhoneNumbers($object));
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

        $this->assertEquals(null, $this->provider->getPhoneNumber($object));
    }

    public function testGetPhoneNumbersFromRelatedObjectNoTargetEntities()
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

        $this->assertSame([], $this->provider->getPhoneNumbers($object));
    }

    public function testGetPhoneNumberFromRelatedObject()
    {
        $object = new TestCustomEntity();
        $object->setUser(new TestUser('user phone'));
        $object->setPhoneHolder(new TestPhoneHolder('123-123'));
        $object->setOther(new SomeEntity());

        $testPhoneHolderProvider = $this->getMock('Oro\Bundle\AddressBundle\Provider\PhoneProviderInterface');
        $testPhoneHolderProvider->expects($this->any())
            ->method('getPhoneNumber')
            ->will(
                $this->returnCallback(
                    function ($object) {
                        /** @var TestPhoneHolder $object */
                        return $object->getPhoneNumber();
                    }
                )
            );
        $this->provider->addPhoneProvider(
            'Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestPhoneHolder',
            $testPhoneHolderProvider
        );

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

        $this->provider->addTargetEntity('Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestUser');
        $this->provider->addTargetEntity('Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestPhoneHolder', -10);

        $this->assertEquals('123-123', $this->provider->getPhoneNumber($object));
    }

    public function testGetPhoneNumbersFromRelatedObject()
    {
        $object = new TestCustomEntity();
        $testUser = new TestUser('user phone');
        $object->setUser($testUser);
        $testPhoneHolder = new TestPhoneHolder('123-123');
        $object->setPhoneHolder($testPhoneHolder);
        $object->setOther(new SomeEntity());

        $testPhoneHolderProvider = $this->getMock('Oro\Bundle\AddressBundle\Provider\PhoneProviderInterface');
        $testPhoneHolderProvider->expects($this->any())
            ->method('getPhoneNumbers')
            ->will(
                $this->returnCallback(
                    function ($object) {
                        /** @var TestPhoneHolder $object */
                        return $object->getPhoneNumbers();
                    }
                )
            );
        $this->provider->addPhoneProvider(
            'Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestPhoneHolder',
            $testPhoneHolderProvider
        );

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

        $this->provider->addTargetEntity('Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestUser');
        $this->provider->addTargetEntity('Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestPhoneHolder', -10);

        $this->assertSame(
            [
                ['123-123', $testPhoneHolder],
                ['user phone', $testUser],
            ],
            $this->provider->getPhoneNumbers($object)
        );
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

    public function getPhoneNumbersProvider()
    {
        $testPhoneHolder = new TestPhoneHolder('123-123');
        $testUser = new TestUser('123-123');
        $testUserWithoutPhone = new TestUser();

        return [
            'null' => [null, []],
            'not obj' => [
                'Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestPhoneHolder',
                []
            ],
            'obj implements PhoneHolderInterface' => [
                $testPhoneHolder,
                [
                    ['123-123', $testPhoneHolder]
                ]
            ],
            'obj has getPhone method' => [
                $testUser,
                [
                    ['123-123', $testUser]
                ]
            ],
            'obj has getPhone method and phone not exists' => [
                $testUserWithoutPhone,
                []
            ]
        ];
    }
}
