<?php

namespace Oro\Bundle\SecurityBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Denies access to an entity when an access to an associated entity is denied.
 */
class AssociationAwareVoter implements VoterInterface
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly string $entityClass,
        private readonly string $associationName
    ) {
    }

    #[\Override]
    public function vote(TokenInterface $token, $object, array $attributes): int
    {
        if (!\is_object($object) || !$object instanceof $this->entityClass) {
            return self::ACCESS_ABSTAIN;
        }

        $associatedEntity = $this->propertyAccessor->getValue($object, $this->associationName);
        if (!\is_object($associatedEntity)) {
            return self::ACCESS_ABSTAIN;
        }

        return $this->getPermission($associatedEntity, $attributes);
    }

    private function getAssociatedEntityAttribute(string $attribute): ?string
    {
        if (BasicPermission::VIEW === $attribute) {
            return BasicPermission::VIEW;
        }

        if (BasicPermission::EDIT === $attribute
            || BasicPermission::CREATE === $attribute
            || BasicPermission::DELETE === $attribute
        ) {
            return BasicPermission::EDIT;
        }

        return null;
    }

    private function getPermission(object $associatedEntity, array $attributes): int
    {
        $result = self::ACCESS_ABSTAIN;
        foreach ($attributes as $attribute) {
            $associatedEntityAttribute = $this->getAssociatedEntityAttribute($attribute);
            if (!$associatedEntityAttribute) {
                continue;
            }

            if (!$this->authorizationChecker->isGranted($associatedEntityAttribute, $associatedEntity)) {
                $result = self::ACCESS_DENIED;
                break;
            }
            if (self::ACCESS_ABSTAIN === $result) {
                $result = self::ACCESS_GRANTED;
            }
        }

        return $result;
    }
}
