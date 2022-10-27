<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\EventListener\MixinListener;
use Oro\Bundle\DataGridBundle\Tools\MixinConfigurationHelper;

class MixinListenerTest extends \PHPUnit\Framework\TestCase
{
    private const MIXIN_NAME = 'new-mixin-for-test-grid';

    /** @var MixinConfigurationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $mixinConfigurationHelper;

    /** @var MixinListener */
    private $listener;

    protected function setUp(): void
    {
        $this->mixinConfigurationHelper = $this->createMock(MixinConfigurationHelper::class);

        $this->listener = new MixinListener($this->mixinConfigurationHelper);
    }

    /**
     * @dataProvider preBuildDataProvider
     */
    public function testOnPreBuild(string $gridName, bool $hasParameter, bool $isApplicable)
    {
        $event = $this->createMock(PreBuild::class);

        $config = $this->createMock(DatagridConfiguration::class);

        $parameters = [];
        if ($hasParameter) {
            $parameters = [MixinListener::GRID_MIXIN => self::MIXIN_NAME];
        }

        $event->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag($parameters));
        $event->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        if ($hasParameter && $isApplicable) {
            $config->expects($this->once())
                ->method('getName')
                ->willReturn($gridName);

            $this->mixinConfigurationHelper->expects($this->once())
                ->method('extendConfiguration')
                ->with($config, self::MIXIN_NAME);
        } else {
            $this->mixinConfigurationHelper->expects($this->never())
                ->method('extendConfiguration');
        }

        $this->listener->onPreBuild($event);
    }

    public function preBuildDataProvider(): array
    {
        return [
            'grid no parameters'   => ['gridName', false, false],
            'grid with parameters' => ['gridName', true, true],
        ];
    }
}
