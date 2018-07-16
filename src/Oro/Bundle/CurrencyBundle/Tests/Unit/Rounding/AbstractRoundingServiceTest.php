<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Rounding;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Rounding\AbstractRoundingService;

abstract class AbstractRoundingServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractRoundingService */
    protected $service;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = $this->getRoundingService();
    }

    /** AbstractRoundingService */
    abstract protected function getRoundingService();

    /**
     * @dataProvider roundProvider
     *
     * @param string $roundingType
     * @param float|integer $value
     * @param integer $precision
     * @param float|integer $expectedValue
     */
    public function testRound($roundingType, $value, $precision, $expectedValue)
    {
        $this->prepareConfigManager($roundingType, $precision);

        $this->assertEquals($expectedValue, $this->service->round($value, $precision));
    }

    /**
     * @param string $roundingType
     * @param int $precision
     */
    protected function prepareConfigManager($roundingType, $precision)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with($this->isType('string'))
            ->willReturn($roundingType);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function roundProvider()
    {
        return [
            'ceil less then half fraction' => [
                'roundingType' => AbstractRoundingService::ROUND_CEILING,
                'value' => 5.5551,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'ceil more then half fraction' => [
                'roundingType' => AbstractRoundingService::ROUND_CEILING,
                'value' => 5.5559,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'floor less then half fraction' => [
                'roundingType' => AbstractRoundingService::ROUND_FLOOR,
                'value' => 5.5551,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'floor more then half fraction' => [
                'roundingType' => AbstractRoundingService::ROUND_FLOOR,
                'value' => 5.5559,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'up positive value' => [
                'roundingType' => AbstractRoundingService::ROUND_UP,
                'value' => 5.5551,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'up negative value' => [
                'roundingType' => AbstractRoundingService::ROUND_UP,
                'value' => -5.5551,
                'precision' => 3,
                'expectedValue' => -5.556,
            ],
            'down positive value' => [
                'roundingType' => AbstractRoundingService::ROUND_DOWN,
                'value' => 5.5559,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'down negative value' => [
                'roundingType' => AbstractRoundingService::ROUND_DOWN,
                'value' => -5.5559,
                'precision' => 3,
                'expectedValue' => -5.555,
            ],
            'half even up to nearest even' => [
                'roundingType' => AbstractRoundingService::ROUND_HALF_EVEN,
                'value' => 5.5555,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'half even down to nearest even' => [
                'roundingType' => AbstractRoundingService::ROUND_HALF_EVEN,
                'value' => 5.5565,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'half even to nearest' => [
                'roundingType' => AbstractRoundingService::ROUND_HALF_EVEN,
                'value' => 5.5551,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'half_up more then half fraction' => [
                'roundingType' => AbstractRoundingService::ROUND_HALF_UP,
                'value' => 5.5555,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'half_up less then half fraction' => [
                'roundingType' => AbstractRoundingService::ROUND_HALF_UP,
                'value' => 5.5554,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'half_down more then half fraction' => [
                'roundingType' => AbstractRoundingService::ROUND_HALF_DOWN,
                'value' => 5.5555,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'half_down less then half fraction' => [
                'roundingType' => AbstractRoundingService::ROUND_HALF_DOWN,
                'value' => 5.5554,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'round zeros' => [
                'roundingType' => AbstractRoundingService::ROUND_HALF_UP,
                'value' => 5.0000,
                'precision' => 3,
                'expectedValue' => 5.000,
            ],
        ];
    }

    /**
     * @throws \Oro\Bundle\CurrencyBundle\Exception\InvalidRoundingTypeException
     */
    public function testInvalidRoundingTypeException()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->willReturn(10);

        $this->expectException('\Oro\Bundle\CurrencyBundle\Exception\InvalidRoundingTypeException');
        $this->expectExceptionMessage('The type of the rounding is not valid "intl" rounding mode.');

        $this->service->round(1.15, 10);
    }
}
