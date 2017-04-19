<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

use Doctrine\Common\EventSubscriber;

use Oro\Bundle\EmailBundle\Acl\Voter\EmailVoter;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Email permissions are added for all roles, as EmailUser is checked anyway by EmailVoter.
 * In Order to remove this permission entirely, parent acl should be set (but it's not currently implemented).
 * @link https://github.com/laboro/platform/pull/6833/files#r53224943
 */
class RoleSubscriber implements EventSubscriber
{
    /** @var ServiceLink */
    protected $aclManagerLink;

    /** @var Role[] */
    protected $insertedRoles = [];

    /**
     * @param ServiceLink $aclManagerLink
     */
    public function __construct(ServiceLink $aclManagerLink)
    {
        $this->aclManagerLink = $aclManagerLink;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
            Events::postFlush,
        ];
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $this->insertedRoles = array_merge(
            $this->insertedRoles,
            array_filter(
                $args->getEntityManager()->getUnitOfWork()->getScheduledEntityInsertions(),
                function ($entity) {
                    return $entity instanceof Role;
                }
            )
        );
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!$this->insertedRoles) {
            return;
        }

        $aclManager = $this->getAclManager();
        foreach ($this->insertedRoles as $role) {
            $sid = $aclManager->getSid($role);
            $mask = 0;
            foreach (EmailVoter::SUPPORTED_CLASSES as $className) {
                $oid = $aclManager->getOid('entity:' . $className);
                foreach (['VIEW', 'CREATE', 'EDIT'] as $permission) {
                    $maskBuilder = $aclManager->getMaskBuilder($oid, $permission);
                    $maskBuilder->add($permission . '_SYSTEM');
                    $mask |= $maskBuilder->get();
                }
                $aclManager->setPermission($sid, $oid, $mask);
            }
        }

        $this->insertedRoles = [];
        $aclManager->flush();
    }

    /**
     * @return AclManager
     */
    protected function getAclManager()
    {
        return $this->aclManagerLink->getService();
    }
}
