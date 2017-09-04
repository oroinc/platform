<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;

class SkippedEntitiesProvider
{
    /**
     * @return array
     */
    public static function getForUpdateAction(): array
    {
        return [
            ProductPrice::class,
        ];
    }

    /**
     * @return array
     */
    public static function getForGetListAction(): array
    {
        return [
            ProductPrice::class,
        ];
    }
}
