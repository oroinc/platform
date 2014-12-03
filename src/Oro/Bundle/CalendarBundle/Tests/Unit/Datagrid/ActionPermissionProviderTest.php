<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CalendarBundle\Datagrid\ActionPermissionProvider;

class ActionPermissionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActionPermissionProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new ActionPermissionProvider();
    }

    /**
     * @param array $params
     * @param array $expected
     *
     * @dataProvider permissionsDataProvider
     */
    public function testGetInvitationPermissions(array $params, array $expected)
    {
        $record = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');

        $record->expects($this->at(0))
            ->method('getValue')
            ->with('invitationStatus')
            ->will($this->returnValue($params['invitationStatus']));

        $record->expects($this->at(1))
            ->method('getValue')
            ->with('parentId')
            ->will($this->returnValue($params['parentId']));

        $result = $this->provider->getInvitationPermissions($record);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function permissionsDataProvider()
    {
        return [
            'invitation child' => [
                'params' => [
                    'invitationStatus' => 'accepted',
                    'parentId' => '3512'
                ],
                'expected' => [
                    'accept'      => false,
                    'decline'     => true,
                    'tentatively' => true,
                    'view'        => true,
                    'update'      => false
                ]
            ],
            'invitation parent' => [
                'params' => [
                    'invitationStatus' => 'accepted',
                    'parentId' => '3512'
                ],
                'expected' => [
                    'accept'      => false,
                    'decline'     => true,
                    'tentatively' => true,
                    'view'        => true,
                    'update'      => false
                ]
            ],
            'not invitation' => [
                'params' => [
                    'invitationStatus' => null,
                    'parentId' => null
                ],
                'expected' => [
                    'accept'      => false,
                    'decline'     => false,
                    'tentatively' => false,
                    'view'        => true,
                    'update'      => true
                ]
            ]
        ];
    }
}
