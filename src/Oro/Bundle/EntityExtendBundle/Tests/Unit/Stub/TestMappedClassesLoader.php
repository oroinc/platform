<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Stub;

use Symfony\Component\Validator\Mapping\Loader\LoaderInterface;

/**
 * Interface for creating a mockable loader with getMappedClasses().
 */
interface TestMappedClassesLoader extends LoaderInterface
{
    public function getMappedClasses(): array;
}
