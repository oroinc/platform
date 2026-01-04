<?php

namespace Oro\Bundle\EntityConfigBundle\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeFamilyManager;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Prevents removal of non deletable attribute families.
 */
class AttributeFamilyVoter extends AbstractEntityVoter implements ServiceSubscriberInterface
{
    public const ATTRIBUTE_DELETE = 'delete';

    protected $supportedAttributes = [self::ATTRIBUTE_DELETE];

    private ContainerInterface $container;

    public function __construct(DoctrineHelper $doctrineHelper, ContainerInterface $container)
    {
        parent::__construct($doctrineHelper);
        $this->container = $container;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_entity_config.manager.attribute_family_manager' => AttributeFamilyManager::class
        ];
    }

    #[\Override]
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        return $this->getAttributeFamilyManager()->isAttributeFamilyDeletable($identifier) ?
            self::ACCESS_ABSTAIN :
            self::ACCESS_DENIED;
    }

    private function getAttributeFamilyManager(): AttributeFamilyManager
    {
        return $this->container->get('oro_entity_config.manager.attribute_family_manager');
    }
}
