<?php

namespace Oro\Bundle\DigitalAssetBundle\Acl\Voter;

use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Prevents removal of digital assets that have child files.
 */
class DigitalAssetDeleteVoter implements VoterInterface
{
    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        if (!$subject instanceof DigitalAsset) {
            return self::ACCESS_ABSTAIN;
        }

        foreach ($attributes as $attribute) {
            if (BasicPermission::DELETE !== $attribute) {
                continue;
            }

            if ($subject->getChildFiles()->count()) {
                return self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
