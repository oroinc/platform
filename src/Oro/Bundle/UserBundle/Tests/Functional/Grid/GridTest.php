<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class GridTest extends WebTestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1])
        );
    }

    /**
     * @dataProvider userSubGridNotContainActionsAndMassActionsProvider
     *
     * @param string $gridName
     */
    public function testUserSubGridNotContainActionsAndMassActions($gridName)
    {
        /** @var Manager $datagridManager */
        $datagridManager = $this->client->getContainer()->get('oro_datagrid.datagrid.manager');
        $datagrid = $datagridManager->getDatagridByRequestParams($gridName);
        $massActions = $datagrid->getConfig()->offsetGetOr(MassActionExtension::ACTION_KEY, []);
        $this->assertEmpty(
            $massActions,
            sprintf(
                'Next mass actions present at user sub-datagrids: "%s", but shouldn\'t!',
                implode('", "', array_keys($massActions))
            )
        );

        $nonApplicableActions = array_keys(
            array_intersect_key(
                array_flip(
                    [
                        'reset_password',
                        'user_activate',
                        'user_disable',
                        'user_enable'
                    ]
                ),
                $datagrid->getConfig()->offsetGetOr(ActionExtension::ACTION_KEY, [])
            )
        );

        $this->assertEmpty(
            $nonApplicableActions,
            'Non-applicable actions exist at datagrid !'
        );
    }

    /**
     * @return array
     */
    public function userSubGridNotContainActionsAndMassActionsProvider()
    {
        return [
            "Grid 'role-view-users-grid'" => [
                'gridName' => 'role-view-users-grid'
            ],
            "Grid 'role-users-grid'" => [
                'gridName' => 'role-users-grid'
            ],
            "Grid 'group-users-grid'" => [
                'gridName' => 'group-users-grid'
            ]
        ];
    }
}
