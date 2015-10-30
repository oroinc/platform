<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Grid;

use Oro\Bundle\DataGridBundle\Tests\Functional\AbstractDatagridTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 */
class EmailGridTest extends AbstractDatagridTestCase
{
    const AUTH_USER = 'simple_user';
    const AUTH_PW = 'simple_password';

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
        $requestData['gridParameters'][$requestData['gridParameters']['gridName']]['_pager']['_per_page'] = 100;

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
                    'expectedResultCount' => 9,
                ],
            ],
            'Email grid filtered by from (admin)' => [
                [
                    'gridParameters' => [
                        'gridName' => 'user-email-grid'
                    ],
                    'gridFilters' => [
                        'user-email-grid[_filter][to][value]' => 'admin',
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
                    'expectedResultCount' => 9,
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function generateBasicAuthHeader(
        $userName = null,
        $userPassword = null,
        $userOrganization = null
    ) {
        $userName = $userName ?: static::AUTH_USER;
        $userPassword = $userPassword ?: static::AUTH_PW;
        $userOrganization = $userOrganization ?: static::AUTH_ORGANIZATION;

        return parent::generateBasicAuthHeader($userName, $userPassword, $userOrganization);
    }
}
