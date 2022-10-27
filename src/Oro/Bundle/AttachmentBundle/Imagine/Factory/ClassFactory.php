<?php

namespace Oro\Bundle\AttachmentBundle\Imagine\Factory;

use Imagine\Factory\ClassFactory as BaseClassFactory;
use Oro\Bundle\AttachmentBundle\Imagine\Loader\Loader;

/**
 * File loader factory.
 *
 * Since the factory has a fixed method of creating a loader, so to add new loader,
 * explicitly re-declare factory method.
 */
class ClassFactory extends BaseClassFactory
{
    private string $protocol;

    public function __construct(string $protocol)
    {
        $this->protocol = $protocol;
    }

    public function createFileLoader($path): object
    {
        return $this->finalize(new Loader($path, $this->protocol));
    }
}
