<?php

namespace Oro\Bundle\SecurityBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategyContextInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Base acl voter interface
 */
interface AclVoterInterface extends VoterInterface, PermissionGrantingStrategyContextInterface
{
    public function addOneShotIsGrantedObserver(OneShotIsGrantedObserver $observer): void;
}
