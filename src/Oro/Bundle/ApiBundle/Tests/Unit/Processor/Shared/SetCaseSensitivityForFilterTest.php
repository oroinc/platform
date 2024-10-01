<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Processor\Shared\SetCaseSensitivityForFilter;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class SetCaseSensitivityForFilterTest extends GetListProcessorTestCase
{
    private const FILTER_NAME = 'someFilter';
    private const SENSITIVITY_CONFIG_OPTION_NAME = 'sensitivity_config_option';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var SetCaseSensitivityForFilter */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->configManager = $this->createMock(ConfigManager::class);

        $this->processor = new SetCaseSensitivityForFilter(
            $this->configManager,
            self::FILTER_NAME,
            self::SENSITIVITY_CONFIG_OPTION_NAME
        );
    }

    public function testProcessWhenNoFilter(): void
    {
        $this->configManager->expects(self::never())
            ->method('get');

        $this->processor->process($this->context);
    }

    public function testProcessForNotComparisonFilter(): void
    {
        $this->configManager->expects(self::never())
            ->method('get');

        $this->context->getFilters()->add(self::FILTER_NAME, $this->createMock(FilterInterface::class));
        $this->processor->process($this->context);
    }

    /**
     * @dataProvider processForComparisonFilterDataProvider
     */
    public function testProcessForComparisonFilter(?bool $optionValue): void
    {
        $filter = $this->createMock(ComparisonFilter::class);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(self::SENSITIVITY_CONFIG_OPTION_NAME)
            ->willReturn($optionValue);

        $filter->expects(self::once())
            ->method('setCaseInsensitive')
            ->with((bool)$optionValue);

        $this->context->getFilters()->add(self::FILTER_NAME, $filter);
        $this->processor->process($this->context);
    }

    public static function processForComparisonFilterDataProvider(): array
    {
        return [[false], [true], [null]];
    }
}
