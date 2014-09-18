<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\ItemInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Entity\NavigationItemInterface;
use Oro\Bundle\NavigationBundle\Entity\Repository\NavigationRepositoryInterface;

class NavigationItemBuilder implements BuilderInterface
{
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
     * @param SecurityContextInterface $securityContext
     * @param EntityManager            $em
     * @param ItemFactory              $factory
     */
    public function __construct(SecurityContextInterface $securityContext, EntityManager $em, ItemFactory $factory)
    {
        $this->securityContext = $securityContext;
        $this->em = $em;
        $this->factory = $factory;
    }

    /**
     * Modify menu by adding, removing or editing items.
     *
     * @param \Knp\Menu\ItemInterface $menu
     * @param array                   $options
     * @param string|null             $alias
     */
    public function build(ItemInterface $menu, array $options = array(), $alias = null)
    {
        $user = $this->securityContext->getToken() ? $this->securityContext->getToken()->getUser() : null;
        $menu->setExtra('type', $alias);
        if (is_object($user)) {
            $currentOrganization = $this->securityContext->getToken()->getOrganizationContext();
            /** @var $entity NavigationItemInterface */
            $entity = $this->factory->createItem($alias, array());

            /** @var $repo NavigationRepositoryInterface */
            $repo = $this->em->getRepository(ClassUtils::getClass($entity));
            $items = $repo->getNavigationItems($user->getId(), $currentOrganization, $alias, $options);
            foreach ($items as $item) {
                $menu->addChild(
                    $alias . '_item_' . $item['id'],
                    array(
                        'extras' => $item,
                        'uri' => $item['url'],
                        'label' => $item['title']
                    )
                );
            }
        }
    }
}
