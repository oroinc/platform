<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\SearchBundle\Engine\Orm;

class OrmIndexListener extends IndexListener
{
    /**
     * @var Orm
     */
    protected $searchEngine;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::onFlush($args);
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        parent::postFlush($args);
    }

    /**
     * @param $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

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
