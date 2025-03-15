<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetSortByConverter;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WidgetSortByConverterTest extends TestCase
{
    private WidgetSortByConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $entityConfigProvider->expects(self::any())
            ->method('hasConfig')
            ->willReturnCallback(function ($className, $property) {
                return 'TestClass' === $className && 'existing' === $property;
            });
        $entityConfigProvider->expects(self::any())
            ->method('getConfig')
            ->with('TestClass', 'existing')
            ->willReturn(new Config($this->createMock(ConfigIdInterface::class), ['label' => 'existingLabel']));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->converter = new WidgetSortByConverter($entityConfigProvider, $translator);
    }

    /**
     * @dataProvider viewValueProvider
     */
    public function testViewValue(?array $passedValue, ?string $expectedValue): void
    {
        self::assertEquals($expectedValue, $this->converter->getViewValue($passedValue));
    }

    public static function viewValueProvider(): array
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
