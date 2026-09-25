<?php

namespace Oro\Bundle\DataAuditBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Names a menu item the way the menu names it, in the order the menu itself resolves it.
 */
class MenuItemNameProvider
{
    private array $storedNames = [];

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ConfigurationProvider $configurationProvider,
        private readonly TranslatorInterface $translator,
        private readonly string $menuUpdateClass
    ) {
    }

    public function getName(MenuUpdateInterface $menuUpdate): string
    {
        $key = (string)$menuUpdate->getKey();

        return $this->getDefaultTitle($menuUpdate) ?? $this->getMenuTitle($key) ?? $key;
    }

    public function getNameForKey(string $menu, Scope $scope, string $key): string
    {
        $menuTitle = $this->getMenuTitle($key);
        if (null !== $menuTitle) {
            return $menuTitle;
        }

        $cacheKey = $menu . '|' . $scope->getId() . '|' . $key;
        if (!\array_key_exists($cacheKey, $this->storedNames)) {
            $repository = $this->doctrine->getRepository($this->menuUpdateClass);
            $criteria = ['menu' => $menu, 'key' => $key];
            $menuUpdate = $repository->findOneBy($criteria + ['scope' => $scope])
                ?? $repository->findOneBy($criteria, ['id' => 'ASC']);

            $this->storedNames[$cacheKey] = null !== $menuUpdate ? $this->getName($menuUpdate) : $key;
        }

        return $this->storedNames[$cacheKey];
    }

    public function getMenuTitle(string $key): ?string
    {
        $label = $this->configurationProvider->getMenuItems()[$key]['label'] ?? null;

        return $label ? $this->translator->trans((string)$label) : null;
    }

    private function getDefaultTitle(MenuUpdateInterface $menuUpdate): ?string
    {
        foreach ($menuUpdate->getTitles() as $title) {
            if ($title instanceof AbstractLocalizedFallbackValue && null === $title->getLocalization()) {
                $text = (string)($title->getString() ?? $title->getText());
                if ('' !== $text) {
                    return $text;
                }
            }
        }

        return null;
    }
}
