<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Oro\Bundle\SearchBundle\Engine\Orm;

class OrmIndexListener extends IndexListener
{
    /**
     * @var Orm
     */
    protected $searchEngine;

    /**
     * To make indexation faster do only one flush instead of several
     */
    protected function indexEntities()
    {
        $this->searchEngine->setNeedFlush(false);

        parent::indexEntities();

        $this->searchEngine->flush();
        $this->searchEngine->setNeedFlush(true);
    }
}
