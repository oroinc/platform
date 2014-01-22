<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class UpdateEntityConfigModelEvent extends NewEntityConfigModelEvent
{
    /**
     * @var array
     */
    public static $scopes = ['extend'];
}
