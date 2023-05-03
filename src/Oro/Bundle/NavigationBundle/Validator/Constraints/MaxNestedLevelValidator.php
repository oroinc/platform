<?php

namespace Oro\Bundle\NavigationBundle\Validator\Constraints;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\MenuUpdateApplierInterface;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Provider\MenuUpdateProvider;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Constraint validator that checks that the target menu item of MenuUpdate does not exceed max nesting level.
 */
class MaxNestedLevelValidator extends ConstraintValidator
{
    private BuilderChainProvider $builderChainProvider;

    private MenuUpdateApplierInterface $menuUpdateApplier;

    public function __construct(
        BuilderChainProvider $builderChainProvider,
        MenuUpdateApplierInterface $menuUpdateApplier
    ) {
        $this->builderChainProvider = $builderChainProvider;
        $this->menuUpdateApplier = $menuUpdateApplier;
    }

    /**
     * {@inheritdoc}
     *
     * @param MenuUpdateInterface $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof MenuUpdateInterface) {
            throw new UnexpectedValueException($value, MenuUpdateInterface::class);
        }

        if (!$constraint instanceof MaxNestedLevel) {
            throw new UnexpectedTypeException($constraint, MaxNestedLevel::class);
        }

        $options = [
            'ignoreCache' => true,
            MenuUpdateProvider::SCOPE_CONTEXT_OPTION => $value->getScope(),
        ];

        $menu = $this->builderChainProvider->get($value->getMenu(), $options);

        $itemExists = (bool)MenuUpdateUtils::findMenuItem($menu, $value->getKey());

        $this->menuUpdateApplier->applyMenuUpdate($value, $menu, $options, null);
        $item = MenuUpdateUtils::findMenuItem($menu, $value->getKey());

        if ($item instanceof ItemInterface) {
            $maxNestingLevel = $menu->getExtra('max_nesting_level', 0);

            if ($maxNestingLevel > 0 && $item->getLevel() > $maxNestingLevel) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->setParameter('{{ label }}', $this->formatValue($item->getLabel()))
                    ->setParameter('{{ max }}', $this->formatValue($maxNestingLevel))
                    ->setCode(MaxNestedLevel::MAX_NESTING_LEVEL_ERROR)
                    ->addViolation();

                if (!$itemExists) {
                    $item->getParent()?->removeChild($item->getName());
                }
            }
        }
    }
}
