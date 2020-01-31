<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The main authorization point of the Security component for Data API.
 * @deprecated this class will be removed in v4.2
 */
class AuthorizationChecker implements AuthorizationCheckerInterface
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var AclGroupProviderInterface */
    private $aclGroupProvider;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param AclGroupProviderInterface     $aclGroupProvider
     * @param DoctrineHelper                $doctrineHelper
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        AclGroupProviderInterface $aclGroupProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->aclGroupProvider = $aclGroupProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted($attributes, $subject = null)
    {
        return $this->authorizationChecker->isGranted($attributes, $subject);
    }
}
