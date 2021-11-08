<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\EventListener\EntityConfigListener;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;

class EntityConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TemplateRendererConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $emailRendererConfigProvider;

    /** @var EntityConfigListener */
    private $listener;

    protected function setUp(): void
    {
        $this->emailRendererConfigProvider = $this->createMock(TemplateRendererConfigProviderInterface::class);

        $this->listener = new EntityConfigListener($this->emailRendererConfigProvider);
    }

    /**
     * @dataProvider changeSetProvider
     */
    public function testPreFlush(string $scope, array $changeSet, bool $shouldClearCache)
    {
        $config = new Config(new FieldConfigId($scope, 'Test\Entity', 'testField'));

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::exactly($scope === 'email' ? 1 : 0))
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($config))
            ->willReturn($changeSet);

        if ($shouldClearCache) {
            $this->emailRendererConfigProvider->expects(self::once())
                ->method('clearCache');
        } else {
            $this->emailRendererConfigProvider->expects(self::never())
                ->method('clearCache');
        }

        $this->listener->preFlush(new PreFlushConfigEvent([$scope => $config], $configManager));
    }

    public function changeSetProvider(): array
    {
        return [
            'email config changed'     => [
                'scope'            => 'email',
                'change'           => ['available_in_template' => [true, false]],
                'shouldClearCache' => true
            ],
            'email config not changed' => [
                'scope'            => 'email',
                'change'           => [],
                'shouldClearCache' => false
            ],
            'not email config'         => [
                'scope'            => 'someConfigScope',
                'change'           => [],
                'shouldClearCache' => false
            ]
        ];
    }
}
