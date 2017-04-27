<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Processor\Mocks;

class ClassWithCloseMethod
{
    public function close()
    {
        /**
         * This is just a dummy class, that has method close, but doesn't use ClosableInterface
         */
    }
}
