<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Stub;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;

class TaggedServicesCompilerPassTraitImplementation
{
    use TaggedServicesCompilerPassTrait {
        registerTaggedServices as public;
        findAndSortTaggedServices as public;
    }
}
