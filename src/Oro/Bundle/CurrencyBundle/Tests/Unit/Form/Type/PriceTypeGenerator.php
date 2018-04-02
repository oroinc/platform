<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\DBAL\Types\MoneyType;

class PriceTypeGenerator
{
    /**
     * @param \PHPUnit_Framework_TestCase $testCase
     * @return PriceType
     */
    public static function createPriceType(\PHPUnit_Framework_TestCase $testCase)
    {
        $priceType = new PriceType(self::getPriceRoundingService($testCase));
        $priceType->setDataClass('Oro\Bundle\CurrencyBundle\Entity\Price');

        return $priceType;
    }

    /**
     * @param \PHPUnit_Framework_TestCase $testCase
     * @return RoundingServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected static function getPriceRoundingService(\PHPUnit_Framework_TestCase $testCase)
    {
        $roundingService = $testCase->getMockBuilder('Oro\Bundle\CurrencyBundle\Rounding\PriceRoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $roundingService->expects($testCase::any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $roundingService->expects($testCase::any())
            ->method('getPrecision')
            ->willReturn(MoneyType::TYPE_SCALE);

        return $roundingService;
    }
}
