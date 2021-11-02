<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Provider\PhoneProvider;
use Oro\Bundle\AddressBundle\Provider\PhoneProviderInterface;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\SomeEntity;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestCustomEntity;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestPhoneHolder;
use Oro\Bundle\AddressBundle\Tests\Unit\Fixtures\TestUser;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class PhoneProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    protected function setUp(): void
    {
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
    }

    /**
     * @param array $phoneProviders [class => [provider id => provider, ...]]
     *
     * @return PhoneProvider
     */
    private function getPhoneProvider(array $phoneProviders): PhoneProvider
    {
        $map = [];
        $containerBuilder = TestContainerBuilder::create();
        foreach ($phoneProviders as $class => $providers) {
            foreach ($providers as $id => $provider) {
                $map[$class][] = $id;
                $containerBuilder->add($id, $provider);
            }
        }

        return new PhoneProvider(
            $map,
            $containerBuilder->getContainer($this),
            $this->extendConfigProvider
        );
    }

    /**
     * @dataProvider getPhoneNumberProvider
     */
    public function testGetPhoneNumber(object|string|null $object, ?string $expected)
    {
        $phoneProvider = $this->createMock(PhoneProviderInterface::class);
        $phoneProvider->expects($this->any())
            ->method('getPhoneNumber')
            ->willReturnCallback(function (TestPhoneHolder $object) {
                return $object->getPhoneNumber();
            });

        $provider = $this->getPhoneProvider([TestPhoneHolder::class => ['provider1' => $phoneProvider]]);
        $this->assertEquals($expected, $provider->getPhoneNumber($object));
    }

    /**
     * @dataProvider getPhoneNumbersProvider
     */
    public function testGetPhoneNumbers(object|string|null $object, array $expected)
    {
        $phoneProvider = $this->createMock(PhoneProviderInterface::class);
        $phoneProvider->expects($this->any())
            ->method('getPhoneNumbers')
            ->willReturnCallback(function (TestPhoneHolder $object) {
                return $object->getPhoneNumbers();
            });

        $provider = $this->getPhoneProvider([TestPhoneHolder::class => ['provider1' => $phoneProvider]]);
        $this->assertSame($expected, $provider->getPhoneNumbers($object));
    }

    public function testGetPhoneNumberFromRelatedObjectNotConfigurableEntity()
    {
        $object = new TestCustomEntity();
        $object->setUser(new TestUser('user phone'));
        $object->setPhoneHolder(new TestPhoneHolder('123-123'));

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(get_class($object))
            ->willReturn(false);
        $this->extendConfigProvider->expects($this->never())
            ->method('getConfig');

        $provider = $this->getPhoneProvider([]);
        $this->assertNull($provider->getPhoneNumber($object));
    }

    public function testGetPhoneNumbersFromRelatedObjectNotConfigurableEntity()
    {
        $object = new TestCustomEntity();
        $object->setUser(new TestUser('user phone'));
        $object->setPhoneHolder(new TestPhoneHolder('123-123'));

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(get_class($object))
            ->willReturn(false);
        $this->extendConfigProvider->expects($this->never())
            ->method('getConfig');

        $provider = $this->getPhoneProvider([]);
        $this->assertSame([], $provider->getPhoneNumbers($object));
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
                    'target_entity' => TestUser::class
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'phoneHolder', 'manyToOne'),
                    'target_entity' => TestPhoneHolder::class
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'other', 'manyToOne'),
                    'target_entity' => SomeEntity::class
                ],
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(get_class($object))
            ->willReturn(true);
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(get_class($object))
            ->willReturn($config);

        $provider = $this->getPhoneProvider([]);
        $this->assertEquals(null, $provider->getPhoneNumber($object));
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
                    'target_entity' => TestUser::class
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'phoneHolder', 'manyToOne'),
                    'target_entity' => TestPhoneHolder::class
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'other', 'manyToOne'),
                    'target_entity' => SomeEntity::class
                ],
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(get_class($object))
            ->willReturn(true);
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(get_class($object))
            ->willReturn($config);

        $provider = $this->getPhoneProvider([]);
        $this->assertSame([], $provider->getPhoneNumbers($object));
    }

    public function testGetPhoneNumberFromRelatedObject()
    {
        $object = new TestCustomEntity();
        $object->setUser(new TestUser('user phone'));
        $object->setPhoneHolder(new TestPhoneHolder('123-123'));
        $object->setOther(new SomeEntity());

        $phoneProvider = $this->createMock(PhoneProviderInterface::class);
        $phoneProvider->expects($this->any())
            ->method('getPhoneNumber')
            ->willReturnCallback(function (TestPhoneHolder $object) {
                return $object->getPhoneNumber();
            });

        $config = new Config(new EntityConfigId('extend', get_class($object)));
        $config->set(
            'relation',
            [
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'user', 'manyToOne'),
                    'target_entity' => TestUser::class
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'phoneHolder', 'manyToOne'),
                    'target_entity' => TestPhoneHolder::class
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'other', 'manyToOne'),
                    'target_entity' => SomeEntity::class
                ],
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(get_class($object))
            ->willReturn(true);
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(get_class($object))
            ->willReturn($config);

        $provider = $this->getPhoneProvider([TestPhoneHolder::class => ['provider1' => $phoneProvider]]);
        $provider->addTargetEntity(TestUser::class);
        $provider->addTargetEntity(TestPhoneHolder::class, -10);

        $this->assertEquals('123-123', $provider->getPhoneNumber($object));
    }

    public function testGetPhoneNumbersFromRelatedObject()
    {
        $object = new TestCustomEntity();
        $testUser = new TestUser('user phone');
        $object->setUser($testUser);
        $testPhoneHolder = new TestPhoneHolder('123-123');
        $object->setPhoneHolder($testPhoneHolder);
        $object->setOther(new SomeEntity());

        $phoneProvider = $this->createMock(PhoneProviderInterface::class);
        $phoneProvider->expects($this->any())
            ->method('getPhoneNumbers')
            ->willReturnCallback(function (TestPhoneHolder $object) {
                return $object->getPhoneNumbers();
            });

        $config = new Config(new EntityConfigId('extend', get_class($object)));
        $config->set(
            'relation',
            [
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'user', 'manyToOne'),
                    'target_entity' => TestUser::class
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'phoneHolder', 'manyToOne'),
                    'target_entity' => TestPhoneHolder::class
                ],
                [
                    'owner' => true,
                    'field_id' => new FieldConfigId('extend', get_class($object), 'other', 'manyToOne'),
                    'target_entity' => SomeEntity::class
                ],
            ]
        );

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(get_class($object))
            ->willReturn(true);
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(get_class($object))
            ->willReturn($config);

        $provider = $this->getPhoneProvider([TestPhoneHolder::class => ['provider1' => $phoneProvider]]);
        $provider->addTargetEntity(TestUser::class);
        $provider->addTargetEntity(TestPhoneHolder::class, -10);

        $this->assertSame(
            [
                ['123-123', $testPhoneHolder],
                ['user phone', $testUser],
            ],
            $provider->getPhoneNumbers($object)
        );
    }

    public function getPhoneNumberProvider(): array
    {
        return [
            'null' => [null, null],
            'not obj' => [TestPhoneHolder::class, null],
            'obj implements PhoneHolderInterface' => [new TestPhoneHolder('123-123'), '123-123'],
            'obj has getPhone method' => [new TestUser('123-123'), '123-123'],
        ];
    }

    public function getPhoneNumbersProvider(): array
    {
        $testPhoneHolder = new TestPhoneHolder('123-123');
        $testUser = new TestUser('123-123');
        $testUserWithoutPhone = new TestUser();

        return [
            'null' => [null, []],
            'not obj' => [TestPhoneHolder::class, []],
            'obj implements PhoneHolderInterface' => [$testPhoneHolder, [['123-123', $testPhoneHolder]]],
            'obj has getPhone method' => [$testUser, [['123-123', $testUser]]],
            'obj has getPhone method and phone not exists' => [$testUserWithoutPhone, []]
        ];
    }
}
