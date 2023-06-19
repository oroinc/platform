<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Environment;

interface TestEntityNameResolverClassesProviderInterface
{
    /**
     * @return array [entity class => [a reason why this class is added to the list, ...], ...]
     */
    public function getEntityClasses(): array;
}
