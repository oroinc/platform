<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\EventListener\EntitySystemConfigListener;
use Oro\Bundle\UserBundle\Entity\User;

class EntitySystemConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = User::class;
    private const CONFIG_KEY = 'alias.config_key';
    private const SETTINGS_KEY = 'alias___config_key';

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EntitySystemConfigListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->listener = new EntitySystemConfigListener(
            $this->doctrine,
            self::ENTITY_CLASS,
            self::CONFIG_KEY
        );
    }

    private function getEntity(int $id): User
    {
        $entity = new User();
        $entity->setId($id);

        return $entity;
    }

    public function testOnFormPreSetData(): void
    {
        $id = 1;
        $entity = $this->getEntity($id);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('find')
            ->with(self::ENTITY_CLASS, $id)
            ->willReturn($entity);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);

        $event = new ConfigSettingsUpdateEvent(
            $this->createMock(ConfigManager::class),
            [self::SETTINGS_KEY => ['value' => $id]]
        );
        $this->listener->onFormPreSetData($event);

        self::assertEquals([self::SETTINGS_KEY => ['value' => $entity]], $event->getSettings());
    }

    public function testOnFormPreSetDataWhenNoSupportedConfigKey(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $settings = ['another___key' => ['value' => 1]];
        $event = new ConfigSettingsUpdateEvent($this->createMock(ConfigManager::class), $settings);
        $this->listener->onFormPreSetData($event);

        self::assertEquals($settings, $event->getSettings());
    }

    public function testOnFormPreSetDataWhenNoEntityIdInConfig(): void
    {
        $entity = $this->getEntity(1);

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $settings = [self::SETTINGS_KEY => ['value' => null]];
        $event = new ConfigSettingsUpdateEvent($this->createMock(ConfigManager::class), $settings);
        $this->listener->onFormPreSetData($event);

        self::assertEquals($settings, $event->getSettings());
    }

    public function testOnFormPreSetDataWhenEntityNotFound(): void
    {
        $id = 1;

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('find')
            ->with(self::ENTITY_CLASS, $id)
            ->willReturn(null);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);

        $event = new ConfigSettingsUpdateEvent(
            $this->createMock(ConfigManager::class),
            [self::SETTINGS_KEY => ['value' => $id]]
        );
        $this->listener->onFormPreSetData($event);

        self::assertEquals([self::SETTINGS_KEY => ['value' => null]], $event->getSettings());
    }

    public function testOnSettingsSaveBeforeWhenNoSupportedConfigKey(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $settings = ['another.key' => ['value' => new \stdClass()]];
        $event = new ConfigSettingsUpdateEvent($this->createMock(ConfigManager::class), $settings);
        $this->listener->onSettingsSaveBefore($event);

        self::assertEquals($settings, $event->getSettings());
    }

    public function testOnSettingsSaveBeforeWithWrongInstance(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $settings = [self::CONFIG_KEY => ['value' => new \stdClass()]];
        $event = new ConfigSettingsUpdateEvent($this->createMock(ConfigManager::class), $settings);
        $this->listener->onSettingsSaveBefore($event);

        self::assertEquals($settings, $event->getSettings());
    }

    public function testOnSettingsSaveBefore(): void
    {
        $id = 1;
        $entity = $this->getEntity($id);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with(self::identicalTo($entity))
            ->willReturn(['id' => $id]);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(self::ENTITY_CLASS)
            ->willReturn($classMetadata);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($em);

        $event = new ConfigSettingsUpdateEvent(
            $this->createMock(ConfigManager::class),
            [self::CONFIG_KEY => ['value' => $entity]]
        );
        $this->listener->onSettingsSaveBefore($event);

        self::assertEquals([self::CONFIG_KEY => ['value' => $id]], $event->getSettings());
    }
}
