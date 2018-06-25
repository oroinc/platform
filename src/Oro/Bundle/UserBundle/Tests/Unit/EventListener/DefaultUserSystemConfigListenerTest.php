<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\EventListener\DefaultUserSystemConfigListener;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class DefaultUserSystemConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DefaultUserProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $defaultUserProvider;

    /** @var DefaultUserSystemConfigListener */
    private $listener;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->defaultUserProvider = $this->createMock(DefaultUserProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->listener = new DefaultUserSystemConfigListener($this->defaultUserProvider, $this->doctrineHelper);
        $this->listener->setAlias('alias');
        $this->listener->setConfigKey('config_key');
    }

    public function testOnFormPreSetData()
    {
        $id = 1;
        $key = $this->getConfigKey();
        $owner = new User();

        $userRepository = $this->getUserRepository($id, $owner);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(User::class)
            ->willReturn($userRepository);

        $this->defaultUserProvider
            ->expects($this->never())
            ->method('getDefaultUser');

        $event = $this->getEvent([$key => ['value' => $id]]);
        $this->listener->onFormPreSetData($event);

        $this->assertEquals([$key => ['value' => $owner]], $event->getSettings());
    }

    public function testOnFormPreSetDataWhenNotSet()
    {
        $key = $this->getConfigKey();
        $owner = new User();

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityRepositoryForClass');

        $this->defaultUserProvider
            ->expects($this->once())
            ->method('getDefaultUser')
            ->with('alias', 'config_key')
            ->willReturn($owner);

        $event = $this->getEvent([$key => ['value' => null]]);
        $this->listener->onFormPreSetData($event);

        $this->assertEquals([$key => ['value' => $owner]], $event->getSettings());
    }

    public function testOnFormPreSetDataWhenUserNotFound()
    {
        $id = 1;
        $key = $this->getConfigKey();
        $owner = new User();

        $userRepository = $this->getUserRepository($id, null);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(User::class)
            ->willReturn($userRepository);

        $this->defaultUserProvider
            ->expects($this->once())
            ->method('getDefaultUser')
            ->with('alias', 'config_key')
            ->willReturn($owner);

        $event = $this->getEvent([$key => ['value' => $id]]);
        $this->listener->onFormPreSetData($event);

        $this->assertEquals([$key => ['value' => $owner]], $event->getSettings());
    }

    public function testOnSettingsSaveBeforeWithWrongArrayStructure()
    {
        $settingsKey = $this->getConfigKey();
        $settings = ['value' => [$settingsKey => new \stdClass()]];
        $event = $this->getEvent($settings);

        $this->listener->onSettingsSaveBefore($event);

        $this->assertEquals(new \stdClass(), $event->getSettings()['value'][$settingsKey]);
    }

    public function testOnSettingsSaveBeforeWithWrongInstance()
    {
        $settingsKey = $this->getConfigKey();
        $settings = [$settingsKey => ['value' => new \stdClass()]];
        $event = $this->getEvent($settings);

        $this->listener->onSettingsSaveBefore($event);

        $this->assertEquals(new \stdClass(), $event->getSettings()[$settingsKey]['value']);
    }

    public function testOnSettingsSaveBefore()
    {
        $owner = $this->getEntity(User::class, ['id' => 1]);
        $event = $this->getEvent(['value' => $owner]);

        $this->listener->onSettingsSaveBefore($event);

        $this->assertEquals(['value' => 1], $event->getSettings());
    }

    /**
     * @param array $settings
     *
     * @return ConfigSettingsUpdateEvent
     */
    private function getEvent(array $settings)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->createMock(ConfigManager::class);

        return new ConfigSettingsUpdateEvent($configManager, $settings);
    }

    /**
     * @return string
     */
    private function getConfigKey()
    {
        return TreeUtils::getConfigKey('alias', 'config_key', ConfigManager::SECTION_VIEW_SEPARATOR);
    }

    /**
     * @param int       $id
     * @param User|null $user
     *
     * @return EntityRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getUserRepository(int $id, User $user = null)
    {
        $userRepository = $this->createMock(EntityRepository::class);
        $userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $id])
            ->willReturn($user);

        return $userRepository;
    }
}
