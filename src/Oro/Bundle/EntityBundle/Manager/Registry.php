<?php

namespace Oro\Bundle\EntityBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry as DoctrineRegistry;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class Registry extends DoctrineRegistry
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function resetManager($name = null)
    {
        $this->doctrineHelper->clearManagerCache();

        return parent::resetManager($name);
    }
}
