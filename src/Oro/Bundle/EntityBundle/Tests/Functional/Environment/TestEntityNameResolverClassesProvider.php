<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Environment;

class TestEntityNameResolverClassesProvider implements TestEntityNameResolverClassesProviderInterface
{
    #[\Override]
    public function getEntityClasses(): array
    {
        return [];
    }
}
