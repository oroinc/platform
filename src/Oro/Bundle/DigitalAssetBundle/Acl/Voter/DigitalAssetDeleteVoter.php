<?php

namespace Oro\Bundle\DigitalAssetBundle\Acl\Voter;

use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Checks if DigitalAsset can be deleted depending on existing child files.
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
            if ($attribute !== 'DELETE') {
                continue;
            }

            if ($subject->getChildFiles()->count()) {
                return self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
