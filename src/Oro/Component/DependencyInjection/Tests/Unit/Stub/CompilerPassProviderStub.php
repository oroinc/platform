<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Stub;

use Oro\Component\DependencyInjection\Compiler\CompilerPassProviderTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Stub for usage of the CompilerPassProviderTrait
 */
class CompilerPassProviderStub
{
    use CompilerPassProviderTrait;

    /**
     * Finds compiler pass for \stdClass
     *
     * @param mixed $container
     *
     * @return \stdClass|null
     */
    public function getStdClassCompilerPass(ContainerBuilder $container)
    {
        return $this->findCompilerPassByClassName($container, \stdClass::class);
    }
}
