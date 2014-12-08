<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CalendarBundle\Datagrid\ActionPermissionProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class ActionPermissionProviderTest extends \PHPUnit_Framework_TestCase
{
    const ADMIN = 1;
    const USER  = 2;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @var ActionPermissionProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new ActionPermissionProvider($this->securityFacade);
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
        $user   = new User();
        $user->setId(self::ADMIN);

        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $record->expects($this->at(0))
            ->method('getValue')
            ->with('invitationStatus')
            ->will($this->returnValue($params['invitationStatus']));

        $record->expects($this->at(1))
            ->method('getValue')
            ->with('parentId')
            ->will($this->returnValue($params['parentId']));

        $record->expects($this->at(2))
            ->method('getValue')
            ->with('ownerId')
            ->will($this->returnValue($params['ownerId']));

        $record->expects($this->at(3))
            ->method('getValue')
            ->with('childrenCount')
            ->will($this->returnValue($params['childrenCount']));

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
                    'parentId' => '3512',
                    'ownerId' => self::ADMIN,
                    'childrenCount' => null
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
                    'parentId' => '3512',
                    'ownerId' => self::ADMIN,
                    'childrenCount' => null
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
                    'parentId' => null,
                    'ownerId' => self::ADMIN,
                    'childrenCount' => 2
                ],
                'expected' => [
                    'accept'      => false,
                    'decline'     => false,
                    'tentatively' => false,
                    'view'        => true,
                    'update'      => true
                ]
            ],
            'other user invitation' => [
                'params' => [
                    'invitationStatus' => 'accepted',
                    'parentId' => '3512',
                    'ownerId' => self::USER,
                    'childrenCount' => 2
                ],
                'expected' => [
                    'accept'      => false,
                    'decline'     => false,
                    'tentatively' => false,
                    'view'        => true,
                    'update'      => false
                ]
            ],
            'without child events' => [
                'params' => [
                    'invitationStatus' => 'accepted',
                    'parentId' => null,
                    'ownerId' => self::ADMIN,
                    'childrenCount' => null
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
