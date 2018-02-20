<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Knp\Menu\ItemInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface;
use Oro\Bundle\NavigationBundle\Entity\Repository\NavigationRepositoryInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Routing\RouterInterface;

class NavigationItemBuilder implements BuilderInterface, FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var EntityManager */
    private $em;

    /** @var ItemFactory */
    private $factory;

    /** @var RouterInterface */
    private $router;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     * @param EntityManager          $em
     * @param ItemFactory            $factory
     * @param RouterInterface        $router
     */
    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        EntityManager $em,
        ItemFactory $factory,
        RouterInterface $router
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->em = $em;
        $this->factory = $factory;
        $this->router = $router;
    }

    /**
     * Modify menu by adding, removing or editing items.
     *
     * @param \Knp\Menu\ItemInterface $menu
     * @param array                   $options
     * @param string|null             $alias
     */
    public function build(ItemInterface $menu, array $options = [], $alias = null)
    {
        $user = $this->tokenAccessor->getUser();
        $menu->setExtra('type', $alias);
        if (is_object($user)) {
            $currentOrganization = $this->tokenAccessor->getOrganization();
            /** @var $entity NavigationItemInterface */
            $entity = $this->factory->createItem($alias, []);

            /** @var $repo NavigationRepositoryInterface */
            $repo = $this->em->getRepository(ClassUtils::getClass($entity));
            $items = $repo->getNavigationItems($user->getId(), $currentOrganization, $alias, $options);
            foreach ($items as $item) {
                $route = $this->getMatchedRoute($item);
                if (!$route || !$this->featureChecker->isResourceEnabled($route, 'routes')) {
                    continue;
                }

                $menu->addChild(
                    $alias . '_item_' . $item['id'],
                    [
                        'extras' => $item,
                        'uri' => $item['url'],
                        'label' => $item['title']
                    ]
                );
            }
        }
    }

    /**
     * Matches a route and return its name.
     *
     * @param array $item
     *
     * @return string|null
     */
    protected function getMatchedRoute($item)
    {
        try {
            //Remove hash if exists
            $itemUrl = explode('#', $item['url']);
            //Remove query if exists
            $itemUrl = explode('?', $itemUrl[0]);

            $routeMatch = $this->router->match($itemUrl[0]);

            return isset($routeMatch['_route']) ? $routeMatch['_route'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
