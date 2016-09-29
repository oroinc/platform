<?php

namespace Oro\Bundle\NavigationBundle\Validator\Constraints;

use Oro\Bundle\NavigationBundle\Exception\InvalidMaxNestingLevelException;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MaxNestedLevelValidator extends ConstraintValidator
{
    /** @var MenuUpdateManager */
    protected $menuUpdateManager;

    /**
     * @param MenuUpdateManager $menuUpdateManager
     */
    public function __construct(MenuUpdateManager $menuUpdateManager)
    {
        $this->menuUpdateManager = $menuUpdateManager;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($entity, Constraint $constraint)
    {
        try {
            $this->menuUpdateManager->checkMaxNestingLevel($entity);
        } catch (InvalidMaxNestingLevelException $e) {
            $this->context->addViolation($e->getMessage());
        }
    }
}
