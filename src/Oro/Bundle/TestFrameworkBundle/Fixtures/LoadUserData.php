<?php

namespace Oro\Bundle\TestFrameworkBundle\Fixtures;

use Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM\LoadUserData as BaseLoadUserData;

/**
 * @deprecated since 1.10
 *
 * @see \Oro\Bundle\TestFrameworkBundle\Migrations\Data\ORM\LoadUserData
*/
class LoadUserData extends BaseLoadUserData
{
    /**
     * @return int
     *
     * @deprecated since 1.10
     */
    public function getOrder()
    {
        return 110;
    }
}
