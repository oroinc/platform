<?php

namespace Oro\Bundle\EntityConfigBundle\Voter;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityManagementConfig;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Prevents removal of digital assets that have child files.
 */
class EntityManagementConfigVoter implements VoterInterface
{
    #[\Override]
    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        if (!($subject instanceof ConfigModel)) {
            return self::ACCESS_ABSTAIN;
        }

        return $this->isEntityManageable($subject);
    }

    private function isEntityManageable(ConfigModel $subject): int
    {
        if ($subject instanceof FieldConfigModel) {
            $subject = $subject->getEntity();
        }

        if (!$subject) {
            return self::ACCESS_ABSTAIN;
        }

        $isEntityManageable = $subject
            ->toArray(EntityManagementConfig::SECTION)[EntityManagementConfig::OPTION] ?? true;

        return $isEntityManageable ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
    }
}
