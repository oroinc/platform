<?php

namespace Oro\Bundle\DataAuditBundle\MenuUpdate;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Menu\ItemInterface;
use Oro\Bundle\DataAuditBundle\Model\MenuAuditValueNormalizer;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\MenuItemToMenuUpdatePropagatorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Remembers what a menu item looked like before it was changed
 */
class MenuUpdateInheritedStatePropagator implements MenuItemToMenuUpdatePropagatorInterface
{
    private array $states = [];

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly MenuAuditValueNormalizer $valueNormalizer
    ) {
    }

    #[\Override]
    public function isApplicable(MenuUpdateInterface $menuUpdate, ItemInterface $menuItem, string $strategy): bool
    {
        return null === $menuUpdate->getId();
    }

    #[\Override]
    public function propagateFromMenuItem(
        MenuUpdateInterface $menuUpdate,
        ItemInterface $menuItem,
        string $strategy
    ): void {
        $this->states[spl_object_id($menuUpdate)] = $this->readState($menuUpdate);
    }

    /**
     * @return array<string, mixed>|null [property => inherited value], or null when nothing was inherited
     */
    public function getInheritedState(MenuUpdateInterface $menuUpdate): ?array
    {
        return $this->states[spl_object_id($menuUpdate)] ?? null;
    }

    public function reset(): void
    {
        $this->states = [];
    }

    /**
     * @return array<string, mixed>
     */
    private function readState(MenuUpdateInterface $menuUpdate): array
    {
        $entityClass = $menuUpdate::class;
        $manager = $this->doctrine->getManagerForClass($entityClass);
        if (null === $manager) {
            return [];
        }

        $metadata = $manager->getClassMetadata($entityClass);
        $state = [];
        foreach ([...$metadata->getFieldNames(), ...$metadata->getAssociationNames()] as $property) {
            if ($this->propertyAccessor->isReadable($menuUpdate, $property)) {
                $state[$property] = $this->readValue($this->propertyAccessor->getValue($menuUpdate, $property));
            }
        }

        return $state;
    }

    private function readValue(mixed $value): mixed
    {
        if (!$value instanceof Collection) {
            return $this->valueNormalizer->normalizeValue($value);
        }

        $values = [];
        foreach ($value as $item) {
            if ($item instanceof AbstractLocalizedFallbackValue) {
                $values[(string)$item->getLocalization()?->getName()] = $item->getString() ?? $item->getText();
            }
        }

        return $values;
    }
}
