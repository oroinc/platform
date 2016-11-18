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
        'user.active' => [
            'name' => 'user.active',
            'label' => 'oro.user.datagrid.views.active',
            'is_default' => true,
            'grid_name' => self::GRID_NAME,
            'type' => GridView::TYPE_PUBLIC,
            'icon' => 'icon-ok',
            'filters' => [
                'auth_status' => [
                    'type' => EnumFilterType::TYPE_NOT_IN,
                    'value' => ['expired'],
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
        'user.cannot_login' => [
            'name' => 'user.cannot_login',
            'label' => 'oro.user.datagrid.views.cannot_login',
            'is_default' => false,
            'grid_name' => self::GRID_NAME,
            'type' => GridView::TYPE_PUBLIC,
            'icon' => 'icon-lock',
            'filters' => [
                'enabled' => [
                    'value' => null,
                ],
                'auth_status' => [
                    'type' => EnumFilterType::TYPE_IN,
                    'value' => ['expired'],
                ],
            ],
            'sorters' => [],
            'columns' => [],
        ],
        'user.disabled' => [
            'name' => 'user.disabled',
            'label' => 'oro.user.datagrid.views.disabled',
            'is_default' => false,
            'grid_name' => self::GRID_NAME,
            'type' => GridView::TYPE_PUBLIC,
            'icon' => 'icon-ban-circle',
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

    /**
     * {@inheritdoc}
     */
    public function getSystemViewsList()
    {
        $views = parent::getSystemViewsList();

        foreach ($views as $view) {
            $name = $view->getName();
            if (!empty($this->systemViews[$name]['icon'])) {
                $view->setIcon($this->systemViews[$name]['icon']);
            }
        }

        return $views;
    }
}
