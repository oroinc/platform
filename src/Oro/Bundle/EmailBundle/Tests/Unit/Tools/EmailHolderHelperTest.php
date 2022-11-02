<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\SomeEntity;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestCustomEntity;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailHolder;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;
use Oro\Bundle\EmailBundle\Tools\EmailHolderHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class EmailHolderHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var EmailHolderHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);

        $this->helper = new EmailHolderHelper($this->extendConfigProvider);
    }

    /**
     * @dataProvider getEmailProvider
     */
    public function testGetEmail(object|string|null $object, ?string $expected)
    {
        $this->assertEquals($expected, $this->helper->getEmail($object));
    }

    public function testGetEmailFromRelatedObjectNotConfigurableEntity()
    {
        $object = new TestCustomEntity();
        $object->setUser(new TestUser('user@example.com'));
        $object->setEmailHolder(new TestEmailHolder('test@example.com'));

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(get_class($object))
            ->willReturn(false);
        $this->extendConfigProvider->expects($this->never())
            ->method('getConfig');

        $this->assertEquals(null, $this->helper->getEmail($object));
    }

    public function testGetEmailFromRelatedObjectNoTargetEntities()
    {
        $object = new TestCustomEntity();
        $object->setUser(new TestUser('user@example.com'));
        $object->setEmailHolder(new TestEmailHolder('test@example.com'));
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
                    'field_id' => new FieldConfigId('extend', get_class($object), 'emailHolder', 'manyToOne'),
                    'target_entity' => TestEmailHolder::class
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

        $this->assertEquals(null, $this->helper->getEmail($object));
    }

    public function testGetEmailFromRelatedObject()
    {
        $object = new TestCustomEntity();
        $object->setUser(new TestUser('user@example.com'));
        $object->setEmailHolder(new TestEmailHolder('test@example.com'));
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
                    'field_id' => new FieldConfigId('extend', get_class($object), 'emailHolder', 'manyToOne'),
                    'target_entity' => TestEmailHolder::class
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

        $this->helper->addTargetEntity(TestUser::class);
        $this->helper->addTargetEntity(TestEmailHolder::class, -10);

        $this->assertEquals('test@example.com', $this->helper->getEmail($object));
    }

    public function getEmailProvider(): array
    {
        return [
            'null' => [
                null,
                null
            ],
            'not obj' => [
                TestEmailHolder::class,
                null
            ],
            'obj implements EmailHolderInterface' => [
                new TestEmailHolder('test@example.com'),
                'test@example.com'
            ],
            'obj has getEmail method' => [
                new TestUser('test@example.com'),
                'test@example.com'
            ],
        ];
    }
}
