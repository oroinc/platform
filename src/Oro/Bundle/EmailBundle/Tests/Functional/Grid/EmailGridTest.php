<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Grid;

use Oro\Bundle\DataGridBundle\Tests\Functional\AbstractDatagridTestCase;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EntityBundle\ORM\OroClassMetadataFactory;
use Oro\Bundle\UserBundle\Entity\User;
use OroEntityProxy\OroEmailBundle\EmailAddressProxy;

class EmailGridTest extends AbstractDatagridTestCase
{
    const AUTH_USER = 'simple_user';
    const AUTH_PW = 'simple_password';

    /**
     * @var User
     */
    protected $user;

    /**
     * @var OroClassMetadataFactory
     */
    protected $metadataFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            'Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailToOtherFolderData',
        ]);

        $this->user = $this->getReference('simple_user');
        $this->metadataFactory = $this->getContainer()->get('oro_entity_extend.orm.metadata_factory');
    }

    protected function tearDown()
    {
        parent::tearDown();

        // Restore EmailAddressProxy as metadata for EmailAddress
        $class = $this->metadataFactory->getMetadataFor(EmailAddressProxy::class);
        $this->metadataFactory->setMetadataFor(EmailAddress::class, $class);
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

    public function testGridWithoutEmailAddressProxy()
    {
        // Remove EmailAddressProxy as metadata for EmailAddress
        $this->metadataFactory->setMetadataFor(EmailAddress::class, null);

        $requestData = [
            'gridParameters' => [
                'gridName' => 'user-email-grid',
                '_pager' => [
                    '_per_page' => 100
                ]
            ],
            'gridFilters' => [],
            'assert' => [],
            'expectedResultCount' => 9
        ];

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
                        'user-email-grid[_filter][from][value]' => 'admin',
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
