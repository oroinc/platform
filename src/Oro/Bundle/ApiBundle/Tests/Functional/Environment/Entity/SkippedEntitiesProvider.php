<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

class SkippedEntitiesProvider
{
    /**
     * @return array
     */
    public static function getForUpdateAction(): array
    {
        return [
            'Oro\Bundle\PricingBundle\Entity\ProductPrice',
        ];
    }

    /**
     * @return array
     */
    public static function getForGetListAction(): array
    {
        return [
            'Oro\Bundle\PricingBundle\Entity\ProductPrice',
        ];
    }
}
