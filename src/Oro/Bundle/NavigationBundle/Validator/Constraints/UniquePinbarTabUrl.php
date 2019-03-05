<?php

namespace Oro\Bundle\NavigationBundle\Validator\Constraints;

use Oro\Bundle\NavigationBundle\Entity\PinbarTab;
use Symfony\Component\Validator\Constraint;

/**
 * PinbarTab URL must be unique for each user.
 */
class UniquePinbarTabUrl extends Constraint
{
    /** @var string  */
    public $message = 'oro.navigation.validator.unique_pinbar_tab_url.error';

    /** @var string */
    public $pinbarTabClass = PinbarTab::class;

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return UniquePinbarTabUrlValidator::class;
    }
}
