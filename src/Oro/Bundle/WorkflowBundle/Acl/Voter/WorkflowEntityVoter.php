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
    protected $supportedAttributes = [BasicPermission::DELETE];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        private readonly ContainerInterface $container
    ) {
        parent::__construct($doctrineHelper);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            WorkflowPermissionRegistry::class
        ];
    }

    #[\Override]
    protected function supportsClass($class)
    {
        return $this->getPermissionRegistry()->supportsClass($class);
    }

    #[\Override]
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        $permissions = $this->getPermissionRegistry()->getPermissionByClassAndIdentifier($class, $identifier);

        return $permissions[$attribute]
            ? self::ACCESS_GRANTED
            : self::ACCESS_DENIED;
    }

    private function getPermissionRegistry(): WorkflowPermissionRegistry
    {
        return $this->container->get(WorkflowPermissionRegistry::class);
    }
}
