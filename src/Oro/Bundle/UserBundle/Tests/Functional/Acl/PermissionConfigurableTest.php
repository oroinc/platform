<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Acl;

use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserACLData;

class PermissionConfigurableTest extends AbstractPermissionConfigurableTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadUserACLData::class]);

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    public function configurablePermissionCapabilitiesProvider()
    {
        yield 'default false' => [
            'config' => [
                'default' => [
                    'default' => false
                ]
            ],
            'action' => 'action:test_action',
            'expected' => false
        ];

        yield 'allow configure permission on test_action' => [
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
        ];

        yield 'disallow configure permission on test_action' => [
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
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function configurablePermissionEntitiesProvider()
    {
        yield 'default false' => [
            'config' => [
                'default' => [
                    'default' => false
                ]
            ],
            'assertGridData' => function (array $gridData) {
                $this->assertEmpty($gridData);
            }
        ];

        yield 'default true' => [
            'config' => [
                'default' => [
                    'default' => true
                ]
            ],
            'assertGridData' => function (array $gridData) {
                $this->assertNotEmpty($gridData);
            }
        ];

        yield 'enable create permission' => [
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
        ];

        yield 'disable create permission' => [
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
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getRole()
    {
        return $this->getReference(LoadUserACLData::ROLE_SYSTEM);
    }

    /**
     * {@inheritdoc}
     */
    protected function getGridName()
    {
        return 'role-permission-grid';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteName()
    {
        return 'oro_user_role_view';
    }
}
