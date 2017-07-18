<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class NameOrOrganization extends Constraint
{
    /** @var string */
    public $firstNameMessage = 'oro.address.validation.invalid_first_name_field';

    /** @var string */
    public $lastNameMessage = 'oro.address.validation.invalid_last_name_field';

    /** @var string */
    public $organizationMessage = 'oro.address.validation.invalid_organization_field';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
