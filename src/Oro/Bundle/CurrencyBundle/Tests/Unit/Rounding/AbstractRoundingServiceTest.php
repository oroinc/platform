<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Rounding;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Exception\InvalidRoundingTypeException;
use Oro\Bundle\CurrencyBundle\Rounding\AbstractRoundingService;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;

abstract class AbstractRoundingServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractRoundingService */
    protected $service;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->service = $this->getRoundingService();
    }

    abstract protected function getRoundingService(): AbstractRoundingService;

    /**
     * @dataProvider roundProvider
     */
    public function testRound(int $roundingType, float|int $value, int $precision, float|int $expectedValue)
    {
        $this->prepareConfigManager($roundingType, $precision);

        $this->assertEquals($expectedValue, $this->service->round($value, $precision));
    }

    protected function prepareConfigManager(string $roundingType, int $precision): void
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with($this->isType('string'))
            ->willReturn($roundingType);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function roundProvider(): array
    {
        return [
            'ceil less then half fraction' => [
                'roundingType' => RoundingServiceInterface::ROUND_CEILING,
                'value' => 5.5551,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'ceil more then half fraction' => [
                'roundingType' => RoundingServiceInterface::ROUND_CEILING,
                'value' => 5.5559,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'floor less then half fraction' => [
                'roundingType' => RoundingServiceInterface::ROUND_FLOOR,
                'value' => 5.5551,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'floor more then half fraction' => [
                'roundingType' => RoundingServiceInterface::ROUND_FLOOR,
                'value' => 5.5559,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'up positive value' => [
                'roundingType' => RoundingServiceInterface::ROUND_UP,
                'value' => 5.5551,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'up negative value' => [
                'roundingType' => RoundingServiceInterface::ROUND_UP,
                'value' => -5.5551,
                'precision' => 3,
                'expectedValue' => -5.556,
            ],
            'down positive value' => [
                'roundingType' => RoundingServiceInterface::ROUND_DOWN,
                'value' => 5.5559,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'down negative value' => [
                'roundingType' => RoundingServiceInterface::ROUND_DOWN,
                'value' => -5.5559,
                'precision' => 3,
                'expectedValue' => -5.555,
            ],
            'half even up to nearest even' => [
                'roundingType' => RoundingServiceInterface::ROUND_HALF_EVEN,
                'value' => 5.5555,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'half even down to nearest even' => [
                'roundingType' => RoundingServiceInterface::ROUND_HALF_EVEN,
                'value' => 5.5565,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'half even to nearest' => [
                'roundingType' => RoundingServiceInterface::ROUND_HALF_EVEN,
                'value' => 5.5551,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'half_up more then half fraction' => [
                'roundingType' => RoundingServiceInterface::ROUND_HALF_UP,
                'value' => 5.5555,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'half_up less then half fraction' => [
                'roundingType' => RoundingServiceInterface::ROUND_HALF_UP,
                'value' => 5.5554,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'half_down more then half fraction' => [
                'roundingType' => RoundingServiceInterface::ROUND_HALF_DOWN,
                'value' => 5.5555,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'half_down less then half fraction' => [
                'roundingType' => RoundingServiceInterface::ROUND_HALF_DOWN,
                'value' => 5.5554,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'round zeros' => [
                'roundingType' => RoundingServiceInterface::ROUND_HALF_UP,
                'value' => 5.0000,
                'precision' => 3,
                'expectedValue' => 5.000,
            ],
        ];
    }

    public function testInvalidRoundingTypeException()
    {
        $this->expectException(InvalidRoundingTypeException::class);
        $this->expectExceptionMessage('The type of the rounding is not valid "intl" rounding mode.');

        $this->configManager->expects($this->once())
            ->method('get')
            ->willReturn(10);

        $this->service->round(1.15, 10);
    }
}
