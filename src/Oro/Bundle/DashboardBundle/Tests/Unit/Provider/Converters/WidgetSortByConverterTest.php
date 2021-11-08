<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetSortByConverter;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class WidgetSortByConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var WidgetSortByConverter */
    private $widgetSortByConverter;

    protected function setUp(): void
    {
        $configId = $this->createMock(ConfigIdInterface::class);
        $config = new Config($configId, ['label' => 'existingLabel']);

        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $entityConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->willReturnCallback(function ($className, $property) {
                return $className === 'TestClass' && $property === 'existing';
            });
        $entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with('TestClass', 'existing')
            ->willReturn($config);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id;
            });

        $this->widgetSortByConverter = new WidgetSortByConverter(
            $entityConfigProvider,
            $translator
        );
    }

    /**
     * @dataProvider viewValueProvider
     */
    public function testViewValue(?array $passedValue, ?string $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->widgetSortByConverter->getViewValue($passedValue));
    }

    public function viewValueProvider(): array
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
