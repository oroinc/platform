<?php

namespace Oro\Bundle\MigrationBundle\Fixture;

/**
 * Should be implemented when the data fixture class has been renamed
 */
interface RenamedFixtureInterface
{
    /**
     * @return string[] list of previous class names for this data fixture
     */
    public function getPreviousClassNames(): array;
}
