<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

class UpdateEntityConfigModelEvent extends NewEntityConfigModelEvent
{
    /**
     * @var array
     */
    public static $scopes = ['extend'];
}
