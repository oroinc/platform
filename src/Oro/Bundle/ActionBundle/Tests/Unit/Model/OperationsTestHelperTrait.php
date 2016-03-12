<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Tests\Unit\AccessorHelperTrait;

trait OperationsTestHelperTrait
{
    use AccessorHelperTrait;

    /**
     * @param array $arrayData
     * @return ActionData
     */
    protected function modifiedData(array $arrayData = [])
    {
        $data = new ActionData($arrayData);

        $this->writePrivate($data, 'modified', true);

        return $data;
    }
}
