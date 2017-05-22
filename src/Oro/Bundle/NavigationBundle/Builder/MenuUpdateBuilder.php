<?php

namespace Oro\Bundle\NavigationBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Knp\Menu\ItemInterface;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Exception\MaxNestingLevelExceededException;
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

    /** @var ManagerRegistry */
    private $registry;

    /** @var string */
    private $scopeType;

    /** @var string */
    private $className;

    /**
     * @param LocalizationHelper $localizationHelper
     * @param ScopeManager       $scopeManager
     * @param ManagerRegistry    $registry
     */
    public function __construct(
        LocalizationHelper $localizationHelper,
        ScopeManager $scopeManager,
        ManagerRegistry $registry
    ) {
        $this->localizationHelper = $localizationHelper;
        $this->scopeManager = $scopeManager;
        $this->registry = $registry;
    }

    /**
     * @param string $scopeType
     */
    public function setScopeType($scopeType)
    {
        $this->scopeType = $scopeType;
    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ItemInterface $menu, array $options = [], $alias = null)
    {
        $scopeType = $menu->getExtra('scope_type', ConfigurationBuilder::DEFAULT_SCOPE_TYPE);
        if ($scopeType !== $this->scopeType) {
            return;
        }

        $scopeContext = array_key_exists(self::SCOPE_CONTEXT_OPTION, $options) ?
            $options[self::SCOPE_CONTEXT_OPTION] : null;
        $updates = $this->getUpdates($menu->getName(), $scopeType, $scopeContext);
        foreach ($updates as $update) {
            MenuUpdateUtils::updateMenuItem($update, $menu, $this->localizationHelper, $options);
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
    private function getUpdates($menuName, $scopeType, $scopeContext = null)
    {
        $scopeIds = $this->scopeManager->findRelatedScopeIdsWithPriority($scopeType, $scopeContext);

        /** @var MenuUpdateRepository $repo */
        $repo = $this->registry->getManagerForClass($this->className)->getRepository($this->className);

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
