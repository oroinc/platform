<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\EventListener\DefaultUserSystemConfigListener;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

class DefaultUserSystemConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    private const CONFIG_KEY = 'alias.config_key';
    private const SETTINGS_KEY = 'alias___config_key';

    /** @var DefaultUserProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $defaultUserProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var DefaultUserSystemConfigListener */
    private $listener;

    protected function setUp(): void
    {
        $this->defaultUserProvider = $this->createMock(DefaultUserProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->listener = new DefaultUserSystemConfigListener(
            $this->defaultUserProvider,
            $this->doctrine,
            self::CONFIG_KEY
        );
    }

    private function getUser(int $id): User
    {
        $user = new User();
        $user->setId($id);

        return $user;
    }

    public function testOnFormPreSetData(): void
    {
        $id = 1;
        $user = $this->getUser($id);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('find')
            ->with(User::class, $id)
            ->willReturn($user);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($em);

        $this->defaultUserProvider->expects(self::never())
            ->method('getDefaultUser');

        $event = new ConfigSettingsUpdateEvent(
            $this->createMock(ConfigManager::class),
            [self::SETTINGS_KEY => ['value' => $id]]
        );
        $this->listener->onFormPreSetData($event);

        self::assertEquals([self::SETTINGS_KEY => ['value' => $user]], $event->getSettings());
    }

    public function testOnFormPreSetDataWhenNoUserIdInConfigAndDefaultUserFound(): void
    {
        $user = $this->getUser(1);

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->defaultUserProvider->expects(self::once())
            ->method('getDefaultUser')
            ->with(self::CONFIG_KEY)
            ->willReturn($user);

        $event = new ConfigSettingsUpdateEvent(
            $this->createMock(ConfigManager::class),
            [self::SETTINGS_KEY => ['value' => null]]
        );
        $this->listener->onFormPreSetData($event);

        self::assertEquals([self::SETTINGS_KEY => ['value' => $user]], $event->getSettings());
    }

    public function testOnFormPreSetDataWhenNoUserIdInConfigAndDefaultUserNotFound(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->defaultUserProvider->expects(self::once())
            ->method('getDefaultUser')
            ->with(self::CONFIG_KEY)
            ->willReturn(null);

        $settings = [self::SETTINGS_KEY => ['value' => null]];
        $event = new ConfigSettingsUpdateEvent($this->createMock(ConfigManager::class), $settings);
        $this->listener->onFormPreSetData($event);

        self::assertEquals($settings, $event->getSettings());
    }

    public function testOnFormPreSetDataWhenUserNotFound(): void
    {
        $id = 1;
        $user = $this->getUser(2);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('find')
            ->with(User::class, $id)
            ->willReturn(null);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($em);

        $this->defaultUserProvider->expects(self::once())
            ->method('getDefaultUser')
            ->with(self::CONFIG_KEY)
            ->willReturn($user);

        $event = new ConfigSettingsUpdateEvent(
            $this->createMock(ConfigManager::class),
            [self::SETTINGS_KEY => ['value' => $id]]
        );
        $this->listener->onFormPreSetData($event);

        self::assertEquals([self::SETTINGS_KEY => ['value' => $user]], $event->getSettings());
    }

    public function testOnSettingsSaveBeforeWhenNoSupportedConfigKey(): void
    {
        $settings = ['another.key' => ['value' => new \stdClass()]];
        $event = new ConfigSettingsUpdateEvent($this->createMock(ConfigManager::class), $settings);
        $this->listener->onSettingsSaveBefore($event);

        self::assertEquals($settings, $event->getSettings());
    }

    public function testOnSettingsSaveBeforeWithWrongInstance(): void
    {
        $settings = [self::CONFIG_KEY => ['value' => new \stdClass()]];
        $event = new ConfigSettingsUpdateEvent($this->createMock(ConfigManager::class), $settings);
        $this->listener->onSettingsSaveBefore($event);

        self::assertEquals($settings, $event->getSettings());
    }

    public function testOnSettingsSaveBefore(): void
    {
        $event = new ConfigSettingsUpdateEvent(
            $this->createMock(ConfigManager::class),
            [self::CONFIG_KEY => ['value' => $this->getUser(1)]]
        );
        $this->listener->onSettingsSaveBefore($event);

        self::assertEquals([self::CONFIG_KEY => ['value' => 1]], $event->getSettings());
    }
}
