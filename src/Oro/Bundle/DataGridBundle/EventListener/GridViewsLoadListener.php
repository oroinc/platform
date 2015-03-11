<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DataGridBundle\Event\GridViewsLoadEvent;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\UserBundle\Entity\User;

use Symfony\Component\Security\Core\SecurityContextInterface;

class GridViewsLoadListener
{
    /**
     * @var ObjectManager
     */
    public $om;

    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @param ObjectManager $om
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(ObjectManager $om, SecurityContextInterface $securityContext)
    {
        $this->om = $om;
        $this->securityContext = $securityContext;
    }

    /**
     * @param GridViewsLoadEvent $event
     */
    public function onViewsLoad(GridViewsLoadEvent $event)
    {
        $gridName = $event->getGridName();
        $currentUser = $this->getCurrentUser();
        if (!$currentUser) {
            return;
        }

        $gridViews = $this->getGridViewRepository()->findGridViews($currentUser, $gridName);
        if (!$gridViews) {
            return;
        }

        $choices = [];
        $views = [];
        foreach ($gridViews as $gridView) {
            $views[] = $gridView->createView()->getMetadata();
            $choices[] = [
                'label' => $gridView->getName(),
                'value' => $gridView->getId(),
            ];
        }

        $newGridViews = $event->getGridViews();
        $newGridViews['choices'] = array_merge($newGridViews['choices'], $choices);
        $newGridViews['views'] = array_merge($newGridViews['views'], $views);

        $event->setGridViews($newGridViews);
    }

    /**
     * @return User
     */
    protected function getCurrentUser()
    {
        if ($token = $this->securityContext->getToken()) {
            $user = $token->getUser();
            if ($user instanceof User) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @return GridViewRepository
     */
    protected function getGridViewRepository()
    {
        return $this->om->getRepository('OroDataGridBundle:GridView');
    }
}
