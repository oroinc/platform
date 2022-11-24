<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Acl;

use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserACLData;

class PermissionConfigurableTest extends AbstractPermissionConfigurableTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadUserACLData::class]);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    public function configurablePermissionCapabilitiesProvider(): array
    {
        return [
            'default false' => [
                'config' => [
                    'default' => [
                        'default' => false
                    ]
                ],
                'action' => 'action:test_action',
                'expected' => false
            ],
            'allow configure permission on test_action' => [
                'config' => [
                    'default' => [
                        'default' => false,
                        'capabilities' => [
                            'test_action' => true
                        ]
                    ]
                ],
                'action' => 'action:test_action',
                'expected' => true
            ],
            'disallow configure permission on test_action' => [
                'config' => [
                    'default' => [
                        'default' => true,
                        'capabilities' => [
                            'test_action' => false
                        ]
                    ]
                ],
                'action' => 'action:test_action',
                'expected' => false
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function configurablePermissionEntitiesProvider(): array
    {
        return [
            'default false' => [
                'config' => [
                    'default' => [
                        'default' => false
                    ]
                ],
                'assertGridData' => function (array $gridData) {
                    $this->assertEmpty($gridData);
                }
            ],
            'default true' => [
                'config' => [
                    'default' => [
                        'default' => true
                    ]
                ],
                'assertGridData' => function (array $gridData) {
                    $this->assertNotEmpty($gridData);
                }
            ],
            'enable create permission' => [
                'config' => [
                    'default' => [
                        'default' => false,
                        'entities' => [
                            TestActivity::class => [
                                'CREATE' => true
                            ]
                        ]
                    ]
                ],
                'assertGridData' => function (array $gridData) {
                    $this->assertCount(1, $gridData);
                    $this->assertHasEntityPermission($gridData, TestActivity::class, 'CREATE');
                    $this->assertNotHasEntityPermission($gridData, TestActivity::class, 'VIEW');
                }
            ],
            'disable create permission' => [
                'config' => [
                    'default' => [
                        'default' => true,
                        'entities' => [
                            TestActivity::class => [
                                'CREATE' => false
                            ]
                        ]
                    ]
                ],
                'assertGridData' => function (array $gridData) {
                    $this->assertNotEmpty($gridData);
                    $this->assertHasEntityPermission($gridData, TestActivity::class, 'VIEW');
                    $this->assertNotHasEntityPermission($gridData, TestActivity::class, 'CREATE');
                }
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getRole(): AbstractRole
    {
        return $this->getReference(LoadUserACLData::ROLE_SYSTEM);
    }

    /**
     * {@inheritdoc}
     */
    protected function getGridName(): string
    {
        return 'role-permission-grid';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteName(): string
    {
        return 'oro_user_role_view';
    }
}
