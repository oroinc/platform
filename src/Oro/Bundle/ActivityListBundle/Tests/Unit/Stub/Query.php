<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Stub;

/**
 * Since the class from doctrine is final, it's necessary to use another one to mock
 */
class Query
{
    /**
     * Gets DQL
     */
    public function getDQL()
    {
    }
}
