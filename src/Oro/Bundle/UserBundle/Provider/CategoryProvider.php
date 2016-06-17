<?php


namespace Oro\Bundle\UserBundle\Provider;


class CategoryProvider
{
    public function getList()
    {
        return [
            'account_management' => [
                'id' => 'account_management',
                'label' => 'Account Management',
                'tab' => true
            ],
            'marketing' => [
                'id' => 'marketing',
                'label' => 'Marketing',
                'tab' => true
            ],
            'sales_data' => [
                'id' => 'sales_data',
                'label' => 'Sales Data',
                'tab' => true
            ],
            'address' => [
                'id' => 'address',
                'label' => 'Address',
                'tab' => false
            ],
            'application' => [
                'id' => 'application',
                'label' => 'Applications',
                'tab' => false
            ],
            'calendar' => [
                'id' => 'calendar',
                'label' => 'Calendar',
                'tab' => false
            ],
            'entity' => [
                'id' => 'entity',
                'label' => 'Entities',
                'tab' => false
            ]
        ];
    }
}