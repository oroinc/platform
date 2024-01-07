<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
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
                        'attachment'
                    ],
                    'from' => [
                        [
                            'table' => Attachment::class,
                            'alias' => 'attachment'
                        ]
                    ]
                ]
            ]
        ];

        parent::__construct($params);
    }
}
