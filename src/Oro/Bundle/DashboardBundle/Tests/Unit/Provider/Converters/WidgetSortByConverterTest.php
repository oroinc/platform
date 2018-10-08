<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetSortByConverter;
use Oro\Bundle\EntityConfigBundle\Config\Config;

class WidgetSortByConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var WidgetSortByConverter */
    protected $widgetSortByConverter;

    public function setUp()
    {
        $configId = $this->createMock('Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface');
        $config = new Config($configId, ['label' => 'existingLabel']);

        $entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $entityConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->will($this->returnCallback(function ($className, $property) {
                return $className === 'TestClass' && $property === 'existing';
            }));
        $entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with('TestClass', 'existing')
            ->will($this->returnValue($config));

        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(function ($id) {
                return $id;
            }));

        $this->widgetSortByConverter = new WidgetSortByConverter(
            $entityConfigProvider,
            $translator
        );
    }

    /**
     * @dataProvider viewValueProvider
     */
    public function testViewValue($passedValue, $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->widgetSortByConverter->getViewValue($passedValue));
    }

    public function viewValueProvider()
    {
        return [
            [
                null,
                null,
            ],
            [
                ['property' => '', 'order' => 'ASC', 'className' => 'TestClass'],
                null,
            ],
            [
                ['property' => 'nonExisting', 'order' => 'ASC', 'className' => 'TestClass'],
                null,
            ],
            [
                ['property' => 'existing', 'order' => 'ASC', 'className' => 'TestClass'],
                'existingLabel oro.dashboard.widget.sort_by.order.asc.label',
            ],
            [
                ['property' => 'existing', 'order' => 'DESC', 'className' => 'TestClass'],
                'existingLabel oro.dashboard.widget.sort_by.order.desc.label',
            ],
        ];
    }
}
