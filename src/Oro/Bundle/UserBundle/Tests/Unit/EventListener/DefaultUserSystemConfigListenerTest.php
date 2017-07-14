<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\EventListener\DefaultUserSystemConfigListener;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

use Oro\Component\Testing\Unit\EntityTrait;

class DefaultUserSystemConfigListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var DefaultUserProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $defaultUserProvider;

    /** @var DefaultUserSystemConfigListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->defaultUserProvider = $this->createMock(DefaultUserProvider::class);
        $this->listener = new DefaultUserSystemConfigListener($this->defaultUserProvider);
        $this->listener->setAlias('alias');
        $this->listener->setConfigKey('config_key');
    }

    public function testOnFormPreSetData()
    {
        $id = 1;
        $key = $this->getConfigKey();
        $owner = new User();

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
        $settingsKey = $this->getConfigKey();
        $owner = $this->getEntity(User::class, ['id' => 1]);
        $event = $this->getEvent([$settingsKey => ['value' => $owner]]);

        $this->listener->onSettingsSaveBefore($event);

        $this->assertEquals([$settingsKey => ['value' => 1]], $event->getSettings());
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
}
