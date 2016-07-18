<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Entity\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DataGridBundle\Extension\GridViews\AbstractViewsList;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;

class ViewListStub extends AbstractViewsList
{
    public function getList()
    {
        $list = new ArrayCollection();
        $systemView1 = new View('view1');
        $systemView1->setDefault(true);

        $systemView2 = new View('view2');
        $list->add($systemView1);
        $list->add($systemView2);

        return $list;
    }

    public function getViewsList()
    {
        return [];
    }
}
