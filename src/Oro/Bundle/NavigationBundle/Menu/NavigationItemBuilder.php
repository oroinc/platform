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
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\SecurityContextInterface;

class NavigationItemBuilder implements BuilderInterface, FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ItemFactory
     */
    private $factory;

    /**
     * @var Router
     */
    private $router;

    /**
     * @param SecurityContextInterface $securityContext
     * @param EntityManager            $em
     * @param ItemFactory              $factory
     * @param Router                   $router
     */
    public function __construct(
        SecurityContextInterface $securityContext,
        EntityManager $em,
        ItemFactory $factory,
        Router $router
    ) {
        $this->securityContext = $securityContext;
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
        $user = $this->securityContext->getToken() ? $this->securityContext->getToken()->getUser() : null;
        $menu->setExtra('type', $alias);
        if (is_object($user)) {
            $currentOrganization = $this->securityContext->getToken()->getOrganizationContext();
            /** @var $entity NavigationItemInterface */
            $entity = $this->factory->createItem($alias, []);

            /** @var $repo NavigationRepositoryInterface */
            $repo = $this->em->getRepository(ClassUtils::getClass($entity));
            $items = $repo->getNavigationItems($user->getId(), $currentOrganization, $alias, $options);
            foreach ($items as $item) {
                $route = $this->matchRoute($item);
                if (!$this->isRouteEnabled($route)) {
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
    protected function matchRoute($item)
    {
        try {
            $routeMatch = $this->router->match($item['url']);

            return isset($routeMatch['_route']) ? $routeMatch['_route'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
