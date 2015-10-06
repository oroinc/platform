<?php

namespace Oro\Bundle\SecurityBundle\Provider;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This service provides information if current user has relation to shared records and
 * if a given object is shared with current user context. SecurityFacade wraps this service.
 */
class ShareProvider
{
    /** @var RegistryInterface */
    protected $registry;

    /** @var MutableAclProvider */
    protected $aclProvider;

    /** @var SecurityIdentityRetrievalStrategyInterface */
    protected $sidRetrievalStrategy;

    /**
     * @param RegistryInterface $registry
     * @param MutableAclProvider $aclProvider
     * @param SecurityIdentityRetrievalStrategyInterface $sidRetrievalStrategy
     */
    public function __construct(
        RegistryInterface $registry,
        MutableAclProvider $aclProvider,
        SecurityIdentityRetrievalStrategyInterface $sidRetrievalStrategy
    ) {
        $this->registry = $registry;
        $this->aclProvider = $aclProvider;
        $this->sidRetrievalStrategy = $sidRetrievalStrategy;
    }

    /**
     * Determines if object is shared with current user context. If record was shared for user through
     * organization or business unit only, this method will return true.
     *
     * @param TokenInterface $token
     * @param object $object
     *
     * @return bool
     */
    public function isObjectSharedWithContext(TokenInterface $token, $object)
    {
        $objectIdentity = ObjectIdentity::fromDomainObject($object);
        try {
            $acl = $this->aclProvider->findAcl($objectIdentity);
        } catch (AclNotFoundException $e) {
            return false;
        }

        $sids = $this->sidRetrievalStrategy->getSecurityIdentities($token);
        foreach ($acl->getObjectAces() as $ace) {
            /** @var Entry $ace */
            foreach ($sids as $sid) {
                /** @var SecurityIdentityInterface $sid */
                if ($sid->equals($ace->getSecurityIdentity())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determines if object is shared exactly for given user. If record was shared for user through
     * organization or business unit only, this method will return false.
     *
     * @param $object
     * @param UserInterface|null $user
     *
     * @return bool
     */
    public function isObjectSharedWithUser($object, UserInterface $user = null)
    {
        if ($user) {
            $objectIdentity = ObjectIdentity::fromDomainObject($object);
            try {
                $acl = $this->aclProvider->findAcl($objectIdentity);
            } catch (AclNotFoundException $e) {
                return false;
            }

            $userSid = UserSecurityIdentity::fromAccount($user);
            foreach ($acl->getObjectAces() as $ace) {
                /** @var Entry $ace */
                if ($userSid->equals($ace->getSecurityIdentity())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determines if user has relation to those records which are shared exactly for given user. If record was shared
     * for user through organization or business unit only, this method will return false.
     *
     * @param UserInterface|null $user
     *
     * @return bool
     */
    public function hasUserSharedRecords(UserInterface $user = null)
    {
        if ($user) {
            /** @var UserSecurityIdentity $userSid */
            $userSid = UserSecurityIdentity::fromAccount($user);
            $identifier = $userSid->getClass() . '-' . $userSid->getUsername();
            $manager = $this->registry->getManager();
            $securityIdentify = $manager->getRepository('OroSecurityBundle:AclSecurityIdentity')->findOneBy(
                [
                    'username' => true,
                    'identifier' => $identifier,
                ]
            );
            if ($securityIdentify && $securityIdentify->getId()) {
                $aclEntry = $manager->getRepository('OroSecurityBundle:AclEntry')->findOneBy(
                    [
                        'securityIdentity' => $securityIdentify
                    ]
                );
                if ($aclEntry && $aclEntry->getId()) {
                    return true;
                }
            }
        }

        return false;
    }
}
