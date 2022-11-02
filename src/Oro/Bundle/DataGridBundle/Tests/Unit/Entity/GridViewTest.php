<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Entity;

use Oro\Bundle\DataGridBundle\Entity\AppearanceType;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\UserBundle\Entity\User;

class GridViewTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provider
     */
    public function testSettersAndGetters(string $property, mixed $value)
    {
        $gridView = new GridView();

        call_user_func([$gridView, 'set' . ucfirst($property)], $value);
        self::assertEquals($value, call_user_func_array([$gridView, 'get' . ucfirst($property)], []));
    }

    public function provider(): array
    {
        $user = new User();
        $user->setUsername('username');

        return [
            ['name', 'test'],
            ['type', GridView::TYPE_PRIVATE],
            ['gridName', 'grid'],
            ['filtersData', ['k' => 'v']],
            ['sortersData', ['k' => 'v']],
            ['appearanceType', new AppearanceType('board')],
            ['appearanceData', ['k' => 'v']],
            ['owner', $user],
            [
                'columnsData',
                ['name' => ['order' => 4]]
            ]
        ];
    }

    public function testCreateView()
    {
        $gridView = new GridView();
        $gridView->setName('name');
        $gridView->setFiltersData(['f' => 'fv']);
        $gridView->setSortersData(['s' => 'sv']);
        $gridView->setAppearanceData(['a' => 'av']);
        $gridView->setAppearanceType(new AppearanceType('board'));
        $gridView->setColumnsData(['name' => ['order' => 4]]);

        $expectedView = new View(
            null,
            ['f' => 'fv'],
            ['s' => 'sv'],
            GridView::TYPE_PRIVATE,
            ['name' => ['order' => 4]],
            'board'
        );
        $expectedView->setLabel('name');
        $expectedView->setAppearanceData(['a' => 'av']);
        self::assertEquals($expectedView, $gridView->createView());
    }
}
