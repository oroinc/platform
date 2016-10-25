<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type;

use Oro\DBAL\Types\MoneyType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;

class PriceTypeGenerator
{
    /**
     * @return PriceType
     */
    public static function createPriceType()
    {
        $priceType = new PriceType(self::getPriceRoundingService());
        $priceType->setDataClass('Oro\Bundle\CurrencyBundle\Entity\Price');

        return $priceType;
    }

    /**
     * @return RoundingServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected static function getPriceRoundingService()
    {
        $generator = new \PHPUnit_Framework_MockObject_Generator();
        $roundingService = $generator
            ->getMock('Oro\Bundle\CurrencyBundle\Rounding\PriceRoundingService', [], [], '', false);

        $roundingService->expects(new \PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $roundingService->expects(new \PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount())
            ->method('getPrecision')
            ->willReturn(MoneyType::TYPE_SCALE);

        return $roundingService;
    }
}
