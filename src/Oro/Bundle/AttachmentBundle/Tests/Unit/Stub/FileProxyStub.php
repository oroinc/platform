<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Stub;

use Doctrine\Persistence\Proxy;
use Oro\Bundle\AttachmentBundle\Entity\File;

class FileProxyStub extends File implements Proxy
{
    private bool $initialized = false;

    public function setInitialized(bool $initialized): void
    {
        $this->initialized = $initialized;
    }

    // @codingStandardsIgnoreStart
    public function __load()
    {
        $this->initialized = true;
    }

    public function __isInitialized()
    {
        return $this->initialized;
    }
    // @codingStandardsIgnoreEnd
}
