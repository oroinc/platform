<?php

namespace Oro\Bundle\ActivityListBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\EventListener\ActivityListListener;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;

class ActivityListChainProvider
{
    /** @var ServiceLink */
    protected $securityFacadeLink;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ActivityListProviderInterface[] */
    protected $providers;

    /**
     * @param ServiceLink    $securityFacadeLink
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ServiceLink $securityFacadeLink, DoctrineHelper $doctrineHelper)
    {
        $this->securityFacadeLink = $securityFacadeLink;
        $this->doctrineHelper     = $doctrineHelper;
    }

    public function addProvider(ActivityListProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @param object $entity
     *
     * @return bool|ActivityList
     */
    public function getActivityListByActivityEntity($entity)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isApplicable($entity)) {
                $list = new ActivityList();
                $list->setVerb(ActivityListListener::STATE_CREATE);
                $list->setRelatedActivityClass($provider->getActivityClass());
                $list->setRelatedActivityId($provider->getActivityId($entity));
                $list->setSubject($provider->getSubject($entity));
                $list->setOwner($entity->getOwner());
                $list->setOrganization($entity->getOrganization());
                $list->setRelatedEntityClass($this->doctrineHelper->getEntityClass($entity));
                $list->setRelatedEntityId($this->doctrineHelper->getSingleEntityIdentifier($entity));

                return $list;
            }
        }

        return false;
    }

    public function getBriefTemplates()
    {
        $templates = [];
        foreach ($this->providers as $provider) {
            $templates[$provider->getActivityClass()] = $provider->getBriefTemplate();
        }

        return $templates;
    }

    public function getFullTemplates()
    {
        $templates = [];
        foreach ($this->providers as $provider) {
            $templates[$provider->getActivityClass()] = $provider->getFullTemplate();
        }

        return $templates;
    }
}
