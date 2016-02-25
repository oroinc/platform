<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Action\Stub;

use Oro\Component\ConfigExpression\Model\AbstractStorage;

class StubStorage extends AbstractStorage
{
    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
