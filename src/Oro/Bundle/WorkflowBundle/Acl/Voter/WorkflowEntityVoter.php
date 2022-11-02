<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\WorkflowBundle\Model\WorkflowPermissionRegistry;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Checks whether a workflow related entity can be deleted.
 */
class WorkflowEntityVoter extends AbstractEntityVoter implements ServiceSubscriberInterface
{
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::DELETE];

    private ContainerInterface $container;

    public function __construct(DoctrineHelper $doctrineHelper, ContainerInterface $container)
    {
        parent::__construct($doctrineHelper);
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_workflow.permission_registry' => WorkflowPermissionRegistry::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function supportsClass($class)
    {
        return $this->getPermissionRegistry()->supportsClass($class);
    }

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        $permissions = $this->getPermissionRegistry()->getPermissionByClassAndIdentifier($class, $identifier);

        return $permissions[$attribute]
            ? self::ACCESS_GRANTED
            : self::ACCESS_DENIED;
    }

    private function getPermissionRegistry(): WorkflowPermissionRegistry
    {
        return $this->container->get('oro_workflow.permission_registry');
    }
}
