<?php

namespace Oro\Bundle\NoteBundle\Provider;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface;
use Oro\Bundle\CommentBundle\Model\CommentProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\NoteBundle\Entity\Note;

class NoteActivityListProvider implements
    ActivityListProviderInterface,
    CommentProviderInterface,
    ActivityListDateProviderInterface
{
    const ACTIVITY_CLASS = 'Oro\Bundle\NoteBundle\Entity\Note';
    const ACL_CLASS = 'Oro\Bundle\NoteBundle\Entity\Note';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ServiceLink */
    protected $entityOwnerAccessorLink;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ServiceLink    $entityOwnerAccessorLink
     */
    public function __construct(DoctrineHelper $doctrineHelper, ServiceLink $entityOwnerAccessorLink)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityOwnerAccessorLink = $entityOwnerAccessorLink;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableTarget(ConfigIdInterface $configId, ConfigManager $configManager)
    {
        $provider = $configManager->getProvider('note');

        return $provider->hasConfigById($configId)
            && $provider->getConfigById($configId)->has('enabled')
            && $provider->getConfigById($configId)->get('enabled');
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
        return [
            'itemView'   => 'oro_note_widget_info',
            'itemEdit'   => 'oro_note_update',
            'itemDelete' => 'oro_api_delete_note'
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
    public function getAclClass()
    {
        return self::ACL_CLASS;
    }

    /**
     * @param Note $entity
     *
     * {@inheritdoc}
     */
    public function getSubject($entity)
    {
        return $this->truncate(strip_tags($entity->getMessage()), 100);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription($entity)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwner($entity)
    {
        /** @var $entity Note */
        return $entity->getOwner();
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedBy($entity)
    {
        /** @var $entity Note */
        return $entity->getUpdatedBy();
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt($entity)
    {
        /** @var $entity Note */
        return $entity->getCreatedAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt($entity)
    {
        /** @var $entity Note */
        return $entity->getUpdatedAt();
    }

    /**
     *  {@inheritdoc}
     */
    public function isDateUpdatable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ActivityList $activityListEntity)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganization($activityEntity)
    {
        /** @var $activityEntity Note */
        return $activityEntity->getOrganization();
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'OroNoteBundle:Note:js/activityItemTemplate.js.twig';
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
        return $entity->getTargetEntities();
    }

    /**
     * {@inheritdoc}
     */
    public function hasComments(ConfigManager $configManager, $entity)
    {
        $config = $configManager->getProvider('comment')->getConfig($entity);

        return $config->is('enabled');
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityOwners($entity, ActivityList $activityList)
    {
        $organization = $this->getOrganization($entity);
        $owner = $this->entityOwnerAccessorLink->getService()->getOwner($entity);

        if (!$organization || !$owner) {
            return [];
        }

        $activityOwner = new ActivityOwner();
        $activityOwner->setActivity($activityList);
        $activityOwner->setOrganization($organization);
        $activityOwner->setUser($owner);
        return [$activityOwner];
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $etc
     * @return string
     */
    protected function truncate($string, $length, $etc = '...')
    {
        if (mb_strlen($string) <= $length) {
            return $string;
        } else {
            $length -= min($length, mb_strlen($etc));
        }
        $string = preg_replace('/\s+?(\S+)?$/u', '', mb_substr($string, 0, $length + 1));

        return mb_substr($string, 0, $length) . $etc;
    }
}
