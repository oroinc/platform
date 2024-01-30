<?php

namespace Oro\Bundle\MigrationBundle\Fixture;

/**
 * Should be implemented when the data fixture class has been renamed.
 */
interface RenamedFixtureInterface
{
    /**
     * Gets the list of previous class names for this data fixture.
     *
     * @return string[]
     */
    public function getPreviousClassNames(): array;
}
