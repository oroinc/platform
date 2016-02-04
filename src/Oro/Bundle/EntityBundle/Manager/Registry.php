<?php

namespace Oro\Bundle\EntityBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry as DoctrineRegistry;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\DependencyInjection\ServiceLink;

class Registry extends DoctrineRegistry
{
    /** @var ServiceLink */
    protected $doctrineHelperLink;

    /**
     * @param ServiceLink $doctrineHelperLink
     */
    public function setDoctrineHelperLink(ServiceLink $doctrineHelperLink)
    {
        $this->doctrineHelperLink = $doctrineHelperLink;
    }

    /**
     * {@inheritdoc}
     */
    public function resetManager($name = null)
    {
        $this->getDoctrineHelper()->clearManagerCache();

        return parent::resetManager($name);
    }

    /**
     * @return DoctrineHelper
     */
    protected function getDoctrineHelper()
    {
        return $this->doctrineHelperLink->getService();
    }
}
