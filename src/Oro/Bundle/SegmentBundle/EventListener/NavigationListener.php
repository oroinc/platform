<?php

namespace Oro\Bundle\SegmentBundle\EventListener;

use Doctrine\ORM\EntityManager;

use Knp\Menu\ItemInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class NavigationListener
{
    /** @var EntityManager */
    protected $em;

    /** @var ConfigProvider */
    protected $entityConfigProvider = null;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param EntityManager  $entityManager
     * @param ConfigProvider $entityConfigProvider
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        EntityManager $entityManager,
        ConfigProvider $entityConfigProvider,
        SecurityFacade $securityFacade
    ) {
        $this->em                   = $entityManager;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->securityFacade       = $securityFacade;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        /** @var ItemInterface $tab */
        $tab = $event->getMenu()->getChild('reports_tab');
        if ($tab && $this->securityFacade->hasLoggedUser()) {
            /** @var Segment[] $segments */
            $segments = $this->em->getRepository('OroSegmentBundle:Segment')
                ->findBy([], ['name' => 'ASC']);

            foreach ($segments as $key => $segment) {
                if (!$this->securityFacade->isGranted('VIEW', sprintf('entity:%s', $segment->getEntity()))) {
                    unset($segments[$key]);
                }
            }

            if (!empty($segments)) {
                $this->addDivider($tab);
                foreach ($segments as $segment) {
                    $config      = $this->entityConfigProvider->getConfig($segment->getEntity());
                    $entityLabel = $config->get('plural_label');
                    $this->getEntityMenuItem($tab, $entityLabel)
                        ->addChild(
                            $this->toIdentifier($segment->getName(), '_segment'),
                            [
                                'label'           => $segment->getName(),
                                'route'           => 'oro_segment_view',
                                'routeParameters' => [
                                    'id' => $segment->getId()
                                ]
                            ]
                        );
                }
            }
        }
    }

    /**
     * Adds a divider to the given menu
     *
     * @param ItemInterface $menu
     */
    protected function addDivider(ItemInterface $menu)
    {
        $menu->addChild(uniqid('divider'))
            ->setLabel('')
            ->setAttribute('class', 'divider')
            ->setExtra('position', 110); // after manage segments, we have 105 there
    }

    /**
     * Get entity menu item for grouping segments by entity
     *
     * @param ItemInterface $tab
     * @param string        $entityLabel
     *
     * @return ItemInterface
     */
    protected function getEntityMenuItem(ItemInterface $tab, $entityLabel)
    {
        $entityItemName = $this->toIdentifier($entityLabel, '_segment_tab');
        $entityItem     = $tab->getChild($entityItemName);
        if (!$entityItem) {
            $entityItem = $tab->addChild(
                $entityItemName,
                [
                    'label'  => $entityLabel,
                    'uri'    => '#',
                    // after divider, all entities will be added in EntityName:ASC order
                    'extras' => ['position' => 115]
                ]
            );
        }

        return $entityItem;
    }

    /**
     * Converts string to correct identifier
     *
     * @param string $id
     * @param string $suffix
     *
     * @return string
     */
    protected function toIdentifier($id, $suffix = '')
    {
        return preg_replace('#[^a-z0-9]+#', '_', strtolower($id)) . $suffix;
    }
}
