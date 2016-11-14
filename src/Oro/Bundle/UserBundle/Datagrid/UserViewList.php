<?php

namespace Oro\Bundle\UserBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Bundle\DataGridBundle\Extension\GridViews\AbstractViewsList;

class UserViewList extends AbstractViewsList
{
    const GRID_NAME = 'users-grid';

    protected $systemViews = [
        [
            'name' => 'user.active',
            'label' => 'oro.user.datagrid.views.active',
            'is_default' => true,
            'grid_name' => self::GRID_NAME,
            'type' => GridView::TYPE_PUBLIC,
            'filters' => [
                'auth_status' => [
                    'type' => EnumFilterType::TYPE_NOT_IN,
                    'value' => ['locked'],
                ],
                'enabled' => [
                    'value' => BooleanFilterType::TYPE_YES,
                ],
            ],
            'sorters' => [],
            'columns' => [
                'enabled' => [
                    'renderable' => false,
                ],
            ],
        ],
        [
            'name' => 'user.cannot_login',
            'label' => 'oro.user.datagrid.views.cannot_login',
            'is_default' => false,
            'grid_name' => self::GRID_NAME,
            'type' => GridView::TYPE_PUBLIC,
            'filters' => [
                'enabled' => [
                    'value' => null,
                ],
                'auth_status' => [
                    'type' => EnumFilterType::TYPE_IN,
                    'value' => ['locked'],
                ],
            ],
            'sorters' => [],
            'columns' => [
                'enabled' => [
                    'renderable' => false,
                ],
            ],
        ],
        [
            'name' => 'user.disabled',
            'label' => 'oro.user.datagrid.views.disabled',
            'is_default' => false,
            'grid_name' => self::GRID_NAME,
            'type' => GridView::TYPE_PUBLIC,
            'filters' => [
                'enabled' => [
                    'value' => BooleanFilterType::TYPE_NO,
                ],
                'auth_status' =>  null,
            ],
            'sorters' => [],
            'columns' => [
                'enabled' => [
                    'renderable' => false,
                ],
                'auth_status' => [
                    'renderable' => false,
                ],
            ],
        ],
    ];

    /**
     * {@inheritDoc}
     */
    protected function getViewsList()
    {
        return $this->getSystemViewsList();
    }
}
