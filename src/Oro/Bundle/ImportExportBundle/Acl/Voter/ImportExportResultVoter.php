<?php

declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Denies access to an import/export result to a user who is not allowed to view the entity
 * the result was produced for.
 */
class ImportExportResultVoter extends AbstractEntityVoter
{
    protected $supportedAttributes = [BasicPermission::VIEW];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
        parent::__construct($doctrineHelper);
    }

    #[\Override]
    protected function getPermissionForAttribute($class, $identifier, $attribute): int
    {
        $result = $this->doctrineHelper->getEntity($class, $identifier);
        if (!$result instanceof ImportExportResult) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        return $this->authorizationChecker->isGranted(
            BasicPermission::VIEW,
            EntityAclExtension::NAME . ':' . $result->getEntity()
        )
            ? VoterInterface::ACCESS_ABSTAIN
            : VoterInterface::ACCESS_DENIED;
    }
}
