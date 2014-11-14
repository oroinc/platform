<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class EmailActivityListProvider implements ActivityListProviderInterface
{
    const ACTIVITY_CLASS = 'Oro\Bundle\EmailBundle\Entity\Email';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ServiceLink */
    protected $doctrineRegistryLink;

    /** @var ServiceLink */
    protected $nameFormatterLink;

    /** @var Router */
    protected $router;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ServiceLink $doctrineRegistryLink,
        ServiceLink $nameFormatterLink,
        Router $router
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->doctrineRegistryLink = $doctrineRegistryLink;
        $this->nameFormatterLink = $nameFormatterLink;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableTarget(ConfigIdInterface $configId, ConfigManager $configManager)
    {
        $provider = $configManager->getProvider('activity');

        return $provider->hasConfigById($configId) && $provider->getConfigById($configId)->has('activities');
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
        return [
            'itemView'   => 'oro_email_view',
            'itemDelete' => 'oro_api_delete_email'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityClass()
    {
        return self::ACTIVITY_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject($entity)
    {
        /** @var $entity Email */
        return $entity->getSubject();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganization($activityEntity)
    {
        /** @var $activityEntity Email */
        return $activityEntity->getFromEmailAddress()->getOwner()->getOrganization();
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ActivityList $activityListEntity)
    {
        /** @var Email $email */
        $email = $this->doctrineRegistryLink->getService()
            ->getRepository($activityListEntity->getRelatedActivityClass())
            ->find($activityListEntity->getRelatedActivityId());
        $owner = $email->getFromEmailAddress()->getOwner();

        // TODO: we need EntityConfig to get view url for an entities
        $classParts = explode('\\', $owner->getClass());
        $routeName = strtolower(array_shift($classParts)) . '_' . strtolower(array_pop($classParts)) . '_view';


        return [
            'owner_name'  => $this->nameFormatterLink->getService()->format($owner),
            'owner_route' => $this->router->generate($routeName, ['id' => $owner->getId()]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'OroEmailBundle:Email:js/activityItemTemplate.js.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityId($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($entity)
    {
        if (is_object($entity)) {
            $entity = $this->doctrineHelper->getEntityClass($entity);
        }

        return $entity == self::ACTIVITY_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetEntities($entity)
    {
        return $entity->getActivityTargetEntities();
    }
}
