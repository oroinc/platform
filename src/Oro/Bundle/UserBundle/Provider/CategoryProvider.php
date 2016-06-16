<?php


namespace Oro\Bundle\UserBundle\Provider;


class CategoryProvider
{
    public function getList()
    {
        return [
            'account_management' => [
                'label' => 'Account Management',
                'tab' => true
            ],
            'marketing' => [
                'label' => 'Marketing',
                'tab' => true
            ],
            'sales_data' => [
                'label' => 'Sales Data',
                'tab' => true
            ],
            'address' => [
                'label' => 'Address',
                'tab' => false
            ],
            'calendar' => [
                'label' => 'Calendar',
                'tab' => false
            ]
        ];
    }
}