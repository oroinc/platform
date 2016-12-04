<?php

namespace Oro\Bundle\NavigationBundle\Builder;

use Knp\Menu\ItemInterface;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Exception\MaxNestingLevelExceededException;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManagerRegistry;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

class MenuUpdateBuilder implements BuilderInterface
{
    const SCOPE_CONTEXT_OPTION = 'scopeContext';

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var ScopeManager */
    private $scopeManager;

    /** @var MenuUpdateManagerRegistry */
    private $registry;

    /**
     * @param LocalizationHelper        $localizationHelper
     * @param ScopeManager              $scopeManager
     * @param MenuUpdateManagerRegistry $registry
     */
    public function __construct(
        LocalizationHelper $localizationHelper,
        ScopeManager $scopeManager,
        MenuUpdateManagerRegistry $registry
    ) {
        $this->localizationHelper = $localizationHelper;
        $this->scopeManager = $scopeManager;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ItemInterface $menu, array $options = [], $alias = null)
    {
        $scopeContext = array_key_exists(self::SCOPE_CONTEXT_OPTION, $options) ?
            $options[self::SCOPE_CONTEXT_OPTION] : null;

        $scopeType = $menu->getExtra('scope_type', ConfigurationBuilder::DEFAULT_AREA);

        $updates = $this->getUpdates($menu->getName(), $scopeType, $scopeContext);
        foreach ($updates as $update) {
            MenuUpdateUtils::updateMenuItem($update, $menu, $this->localizationHelper);
        }

        $this->applyDivider($menu);

        /** @var ItemInterface $item */
        foreach ($menu->getChildren() as $item) {
            $item = MenuUpdateUtils::getItemExceededMaxNestingLevel($menu, $item);
            if ($item) {
                throw new MaxNestingLevelExceededException(
                    sprintf(
                        "Item \"%s\" exceeded max nesting level in menu \"%s\".",
                        $item->getLabel(),
                        $menu->getLabel()
                    )
                );
            }
        }
    }

    /**
     * @param string     $menuName
     * @param string     $scopeType
     * @param array|null $scopeContext
     *
     * @return array
     */
    public function getUpdates($menuName, $scopeType, $scopeContext = null)
    {
        $scopeIds = $this->scopeManager->findRelatedScopeIdsWithPriority($scopeType, $scopeContext);

        /** @var MenuUpdateRepository $repo */
        $repo = $this->registry->getManager($scopeType)->getRepository();

        return $repo->findMenuUpdatesByScopeIds($menuName, $scopeIds);
    }

    /**
     * @param ItemInterface $item
     */
    private function applyDivider(ItemInterface $item)
    {
        if ($item->getExtra('divider', false)) {
            $class = trim(sprintf("%s %s", $item->getAttribute('class', ''), 'divider'));
            $item->setAttribute('class', $class);
        }

        foreach ($item->getChildren() as $child) {
            $this->applyDivider($child);
        }
    }
}
