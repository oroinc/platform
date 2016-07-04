<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\GridViews;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\UserBundle\Entity\User;

class GridViewManagerStub extends GridViewManager
{

    public function __construct()
    {
        return $this;
    }

    /**
     * @param $user
     * @param $gridName
     * @return array
     */
    public function getAllGridViews($user, $gridName)
    {
        $currentUser = new User();

        $systemView = new View('first');
        $view1 = new GridView();
        $view1->setId(1);
        $view1->setOwner($currentUser);
        $view1->setName('view1');
        $view2 = new GridView();
        $view2->setId(2);
        $view2->setName('view2');
        $view2->setOwner($currentUser);
        $gridViews = [
            'system' => [
                $systemView
            ],
            'user' => [$view1, $view2]
        ];
        
        return $gridViews;
    }
}