<?php

namespace Oro\Component\Action\Tests\Unit\Action\Stub;

use Oro\Component\Action\Model\AbstractStorage;

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
