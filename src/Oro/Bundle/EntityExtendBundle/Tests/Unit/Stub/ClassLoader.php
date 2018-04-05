<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Stub;

use Oro\Component\PhpUtils\ClassLoader as BaseClassLoader;

class ClassLoader extends BaseClassLoader
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        spl_autoload_register([$this, 'loadClass'], true, true);
    }
}
