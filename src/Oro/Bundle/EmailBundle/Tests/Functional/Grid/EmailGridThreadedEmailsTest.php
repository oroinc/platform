<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Grid;

use Oro\Bundle\DataGridBundle\Tests\Functional\AbstractDatagridTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class EmailGridThreadedEmailsTest extends AbstractDatagridTestCase
{
    /** @var User */
    protected $user;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader('simple_user', 'simple_password'));

        $this->loadFixtures([
            'Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailThreadedData',
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
            'Email grid w/o filters' => [
                [
                    'gridParameters' => [
                        'gridName' => 'user-email-grid',
                    ],
                    'gridFilters' => [],
                    'assert' => [],
                    'expectedResultCount' => 3,
                ],
            ],
            'Email grid filter by subject contains `Introduction` (in head email)' => [
                [
                    'gridParameters' => [
                        'gridName' => 'user-email-grid'
                    ],
                    'gridFilters' => [
                        'user-email-grid[_filter][subject][value]' => 'Introduction',
                        'user-email-grid[_filter][subject][type]' => 1,
                    ],
                    'assert' => [],
                    'expectedResultCount' => 2,
                ],
            ],
            'Email grid filtered by subject contains `Order` (not in head email), ' => [
                [
                    'gridParameters' => [
                        'gridName' => 'user-email-grid'
                    ],
                    'gridFilters' => [
                        'user-email-grid[_filter][subject][value]' => 'Order',
                        'user-email-grid[_filter][subject][type]' => 1,
                    ],
                    'assert' => [],
                    'expectedResultCount' => 2,
                ],
            ],
            'Email grid filtered by subject contains `Opportunities` (not in head email), ' => [
                [
                    'gridParameters' => [
                        'gridName' => 'user-email-grid'
                    ],
                    'gridFilters' => [
                        'user-email-grid[_filter][subject][value]' => 'Confirmation',
                        'user-email-grid[_filter][subject][type]' => 1,
                    ],
                    'assert' => [],
                    'expectedResultCount' => 1,
                ],
            ],
        ];
    }
}
