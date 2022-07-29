<?php

namespace Oro\Bundle\MigrationBundle\Event;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationState;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Migration life cycle base event
 */
class MigrationLifeCycleEvent extends Event
{
    protected MigrationState $state;

    public function __construct(MigrationState $state)
    {
        $this->state = $state;
    }

    public function getMigration(): Migration
    {
        return $this->state->getMigration();
    }
}
