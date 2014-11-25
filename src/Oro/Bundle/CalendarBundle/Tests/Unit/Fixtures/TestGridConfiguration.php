<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures;


use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class TestGridConfiguration extends DatagridConfiguration
{
    public function __construct()
    {
        $params = [
            'source' => [
                'type' => 'orm',
                'query' => [
                    'select' => [
                        'sc.id',
                        'sc.name as name',
                        'o.name as organization',
                    ],
                    'from' => [
                        [
                            'table' => 'Oro\Bundle\CalendarBundle\Entity\SystemCalendar',
                            'alias' => 'sc',
                        ],
                    ],
                    'join' => [
                        'inner' => [
                            [
                                'join' => 'sc.organization',
                                'alias' => 'o',
                            ],
                        ]
                    ],
                ],
            ],
        ];

        parent::__construct($params);
    }
}
