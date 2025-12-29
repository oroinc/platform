<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\EventListener\EntityConfigListener;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityConfigListenerTest extends TestCase
{
    private TemplateRendererConfigProviderInterface&MockObject $emailRendererConfigProvider;
    private EntityConfigListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->emailRendererConfigProvider = $this->createMock(TemplateRendererConfigProviderInterface::class);

        $this->listener = new EntityConfigListener($this->emailRendererConfigProvider);
    }

    /**
     * @dataProvider changeSetProvider
     */
    public function testPreFlush(
        string $scope,
        array $changeSet,
        ?int $modelId = null,
        bool $shouldClearCache
    ): void {
        $className = 'Test\Entity';
        $fieldName = 'testField';

        $exactlyCalled = $scope === 'email' ? 1 : 0;
        $config = new Config(new FieldConfigId($scope, $className, $fieldName));

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::exactly($exactlyCalled))
            ->method('getConfigChangeSet')
            ->with(self::identicalTo($config))
            ->willReturn($changeSet);

        $configManager->expects(self::exactly($exactlyCalled))
            ->method('getConfigModelId')
            ->with($className, $fieldName)
            ->willReturn($modelId);

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
                'id'               => 1,
                'shouldClearCache' => true
            ],
            'email config not changed' => [
                'scope'            => 'email',
                'change'           => [],
                'id'               => 1,
                'shouldClearCache' => false
            ],
            'not email config'         => [
                'scope'            => 'someConfigScope',
                'change'           => [],
                'id'               => 1,
                'shouldClearCache' => false
            ],
            'new email config'     => [
                'scope'            => 'email',
                'change'           => [],
                'id'               => null,
                'shouldClearCache' => true
            ],
        ];
    }
}
