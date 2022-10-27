<?php

namespace Oro\Bundle\SecurityBundle\Authorization;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Checks if the attributes are granted against the current authentication token and optionally supplied subject.
 */
trait AuthorizationCheckerTrait
{
    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param string|string[] $attributes
     * @param mixed $subject
     *
     * @return bool
     */
    private function isAttributesGranted(
        AuthorizationCheckerInterface $authorizationChecker,
        $attributes,
        $subject = null
    ): bool {
        $attributes = \is_array($attributes) ? $attributes : [$attributes];
        foreach ($attributes as $attribute) {
            if (!$authorizationChecker->isGranted($attribute, $subject)) {
                return false;
            }
        }

        return true;
    }
}
