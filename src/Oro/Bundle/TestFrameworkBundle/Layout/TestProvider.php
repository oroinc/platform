<?php

namespace Oro\Bundle\TestFrameworkBundle\Layout;

use Oro\Bundle\TestFrameworkBundle\Exception\LayoutChildWithInvisibleParentException;

/**
 * Provides test methods for layout blocks
 */
class TestProvider
{
    /**
     * @return bool
     */
    public function getFalse(): bool
    {
        return false;
    }

    /**
     * @throws LayoutChildWithInvisibleParentException
     */
    public function getChildWithInvisibleParentException()
    {
        throw new LayoutChildWithInvisibleParentException();
    }
}
