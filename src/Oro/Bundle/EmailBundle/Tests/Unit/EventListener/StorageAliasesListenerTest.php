<?php

namespace EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\EventListener\StorageAliasesListener;
use Oro\Bundle\EntityPaginationBundle\Event\StorageAliasesEvent;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class StorageAliasesListenerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @param $input
     * @param $result
     *
     * @dataProvider isApplicableProvider
     */
    public function testSetAliases($input, $result)
    {
        $event = new StorageAliasesEvent();

        $listener = new StorageAliasesListener(DatagridConfiguration::create($input));

        $listener->onStorageAliaseEvent($event);

        $this->assertEquals($result, $event->getAliases());

    }

    /**
     * @param $input
     * @param $result
     *
     * @dataProvider isNotApplicableProvider
     */
    public function testSetAliasesWithNotValidConfig($input, $result)
    {
        $event = new StorageAliasesEvent();

        $listener = new StorageAliasesListener(DatagridConfiguration::create($input));

        $listener->onStorageAliaseEvent($event);

        $this->assertEquals($result, $event->getAliases());

    }

    public function isApplicableProvider()
    {
        return [
            [
                'input' => [
                    'options' => [
                        'storage' => [
                            'aliases' => [
                                'target_class' => 'Oro\Bundle\EmailBundle\Entity\EmailUser',
                                'alias'        => 'Oro\Bundle\EmailBundle\Entity\Email'
                            ]
                        ],
                    ],
                ],
                'result' => [
                     'Oro\Bundle\EmailBundle\Entity\EmailUser' => 'Oro\Bundle\EmailBundle\Entity\Email'
                ]
            ],
        ];
    }

    public function isNotApplicableProvider()
    {
        return [
            [
                'input' => [
                    'options' => [
                        'storage' => [
                            'aliases' => [
                                'target_class' => 'Oro\Bundle\EmailBundle\Entity\EmailUser',
                            ]
                        ],
                    ],
                ],
                'result' => [

                ]
            ],
        ];
    }


}