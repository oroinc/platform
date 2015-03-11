<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Entity;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\UserBundle\Entity\User;

class GridViewTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provider
     */
    public function testSettersAndGetters($property, $value)
    {
        $gridView = new GridView();

        call_user_func_array(array($gridView, 'set' . ucfirst($property)), array($value));
        $this->assertEquals($value, call_user_func_array(array($gridView, 'get' . ucfirst($property)), array()));
    }

    public function provider()
    {
        $user = new User();
        $user->setUsername('username');

        return [
            ['name', 'test'],
            ['type', GridView::TYPE_PRIVATE],
            ['gridName', 'grid'],
            ['filtersData', ['k' => 'v']],
            ['sortersData', ['k' => 'v']],
            ['owner', $user],
        ];
    }

    public function testCreateView()
    {
        $gridView = new GridView();
        $gridView->setName('name');
        $gridView->setFiltersData(['f' => 'fv']);
        $gridView->setSortersData(['s' => 'sv']);

        $this->assertEquals(new View(null, ['f' => 'fv'], ['s' => 'sv'], GridView::TYPE_PRIVATE), $gridView->createView());
    }
}
