<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Token;

/**
 * Properly combines OrganizationAwareTokenTrait and AuthenticatedTokenTrait to add both roles and organization when
 * serializing.
 */
trait RolesAndOrganizationAwareTokenTrait
{
    use AuthenticatedTokenTrait {
        AuthenticatedTokenTrait::__serialize as protected authenticatedTokenTraitSerialize;
        AuthenticatedTokenTrait::__unserialize as protected authenticatedTokenTraitUnserialize;
    }
    use OrganizationAwareTokenTrait {
        OrganizationAwareTokenTrait::__serialize as protected organizationAwareTraitSerialize;
        OrganizationAwareTokenTrait::__unserialize as protected organizationAwareTraitUnserialize;
    }

    public function __serialize(): array
    {
        static $inSerialize = false;

        if ($inSerialize) {
            return [];
        }

        $inSerialize = true;

        $data = [
            $this->authenticatedTokenTraitSerialize(),
            $this->organizationAwareTraitSerialize(),
            parent::__serialize()
        ];

        $inSerialize = false;

        return $data;
    }

    public function __unserialize(array $serialized): void
    {
        static $isInUnserialize = false;

        if ($isInUnserialize) {
            return;
        }

        $isInUnserialize = true;

        $this->authenticatedTokenTraitUnserialize($serialized[0]);
        $this->organizationAwareTraitUnserialize($serialized[1]);
        parent::__unserialize($serialized[2]);

        $isInUnserialize = false;
    }
}
