<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * NameOrOrganization constraint. It's data is passed to js validator as params.
 */
class NameOrOrganization extends Constraint
{
    /** @var string */
    public $firstNameMessage = 'oro.address.validation.invalid_first_name_field';

    /** @var string */
    public $lastNameMessage = 'oro.address.validation.invalid_last_name_field';

    /** @var string */
    public $organizationMessage = 'oro.address.validation.invalid_organization_field';

    /**
     * Form name parent form (which also has parent). This helps to distinguish between different addresses in one form
     * for js validation.
     * @see Resources/public/js/validator/name-or-organization.js
     *
     * @var string
     */
    public $parentFormName;

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
