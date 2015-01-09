<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

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
                    'form' => [
                        [
                            'table' => 'OroAttachmentBundle:Attachment',
                            'alias' => 'attachment'
                        ]
                    ]
                ]
            ]
        ];

        parent::__construct($params);
    }
}
