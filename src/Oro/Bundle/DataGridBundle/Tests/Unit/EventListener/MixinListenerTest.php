<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\EventListener\MixinListener;

class MixinListenerTest extends \PHPUnit_Framework_TestCase
{
    const MIXIN_NAME = 'new-mixin-for-test-grid';

    /** @var MixinListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mixinConfigurationHelper;

    protected function setUp()
    {
        $this->mixinConfigurationHelper = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Tools\MixinConfigurationHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new MixinListener($this->mixinConfigurationHelper);
    }

    /**
     * @param string $gridName
     * @param bool   $hasParameter
     * @param bool   $isApplicable
     *
     * @dataProvider preBuildDataProvider
     */
    public function testOnPreBuild($gridName, $hasParameter, $isApplicable)
    {
        $event = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Event\PreBuild')
            ->disableOriginalConstructor()
            ->getMock();

        $config = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $parameters = [];
        if ($hasParameter) {
            $parameters = [MixinListener::GRID_MIXIN => self::MIXIN_NAME];
        }

        $event
            ->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue(new ParameterBag($parameters)));
        $event
            ->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config));


        if ($hasParameter && $isApplicable) {
            $config
                ->expects($this->once())
                ->method('getName')
                ->will($this->returnValue($gridName));

            $this->mixinConfigurationHelper
                ->expects($this->once())
                ->method('extendConfiguration')
                ->with($this->equalTo($config), $this->equalTo(self::MIXIN_NAME));
        } else {
            $this->mixinConfigurationHelper
                ->expects($this->never())
                ->method('extendConfiguration');
        }

        $this->listener->onPreBuild($event);
    }

    /**
     * @return array
     */
    public function preBuildDataProvider()
    {
        return [
            'grid no parameters'   => ['gridName', false, false],
            'grid with parameters' => ['gridName', true, true],
        ];
    }
}
