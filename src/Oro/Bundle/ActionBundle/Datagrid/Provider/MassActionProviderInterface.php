<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Provider;

/**
 * Defines the contract for providing mass actions for datagrids.
 */
interface MassActionProviderInterface
{
    /**
     * @return array
     */
    public function getActions();
}
