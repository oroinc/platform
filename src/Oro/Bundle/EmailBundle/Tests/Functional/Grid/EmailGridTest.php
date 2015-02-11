<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Grid;

use Oro\Bundle\DataGridBundle\Tests\Functional\AbstractDatagridTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailGridTest extends AbstractDatagridTestCase
{
    /**
     * @var User
     */
    protected $user;

    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            'Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData',
        ]);

        $this->user = $this->getReference('simple_user');
    }

    /**
     * @dataProvider gridProvider
     *
     * @param array $requestData
     */
    public function testGrid($requestData)
    {
        $requestData['gridParameters'][$requestData['gridParameters']['gridName']]['userId'] =
            $this->getReference('simple_user')->getId();

        parent::testGrid($requestData);
    }

    /**
     * {@inheritdoc}
     */
    public function gridProvider()
    {
        return [
            'Email grid' => [
                [
                    'gridParameters' => [
                        'gridName' => 'user-email-grid',
                    ],
                    'gridFilters' => [],
                    'assert' => [],
                    'expectedResultCount' => 10,
                ],
            ],
            'Email grid filtered by from (admin)' => [
                [
                    'gridParameters' => [
                        'gridName' => 'user-email-grid'
                    ],
                    'gridFilters' => [
                        'user-email-grid[_filter][fromEmailExpression][value]' => 'admin',
                    ],
                    'assert' => [],
                    'expectedResultCount' => 0,
                ],
            ],
            'Email grid filtered by to' => [
                [
                    'gridParameters' => [
                        'gridName' => 'user-email-grid'
                    ],
                    'gridFilters' => [
                        'user-email-grid[_filter][to][value]' => 'simple_user@example.com',
                    ],
                    'assert' => [],
                    'expectedResultCount' => 10,
                ],
            ],
        ];
    }
}
