<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\UserBundle\Datagrid\RolePermissionDatasource;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Model\PrivilegeCategory;

class RolePermissionDatasourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $permissionManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclRoleHandler;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $categoryProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configEntityManager;

    /** @var RolePermissionDatasource */
    protected $datasource;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->permissionManager = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclRoleHandler = $this->getMockBuilder('Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryProvider = $this->getMockBuilder('Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configEntityManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->datasource = new RolePermissionDatasource(
            $this->translator,
            $this->permissionManager,
            $this->aclRoleHandler,
            $this->categoryProvider,
            $this->configEntityManager
        );
    }

    public function testGetResults()
    {
        $role = new Role();
        $parameters = new ParameterBag();
        $parameters->add(['role' => $role]);
        $datagridConfig = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $grid = new Datagrid('test', $datagridConfig, $parameters);

        $this->datasource->process($grid, []);

        $privilege1 = new AclPrivilege();
        $privilege1->setIdentity(new AclPrivilegeIdentity('entity:Acme\Test1Entity', 'test entity'));
        $privilege1->setCategory('testCategory');
        $privilege1ViewPermission = new AclPermission('VIEW', 4);
        $privilege1CreatePermission = new AclPermission('CREATE', 3);
        $privilege1EditPermission = new AclPermission('EDIT', 5);
        $privilege1DeletePermission = new AclPermission('DELETE', 0);
        $privilege1CustomPermission = new AclPermission('CUSTOM', 1);
        $privilege1TestPermission = new AclPermission('TEST', 2);
        $privilege1->addPermission($privilege1TestPermission);
        $privilege1->addPermission($privilege1ViewPermission);
        $privilege1->addPermission($privilege1EditPermission);
        $privilege1->addPermission($privilege1CustomPermission);
        $privilege1->addPermission($privilege1DeletePermission);
        $privilege1->addPermission($privilege1CreatePermission);
        $privilege1Field1 = new AclPrivilege();
        $privilege1Field1->setIdentity(new AclPrivilegeIdentity('entity:Acme\Test1Entity::field1', 'field1'));
        $privilege1Field1ViewPermission = new AclPermission('VIEW', 3);
        $privilege1Field1CreatePermission = new AclPermission('CREATE', 4);
        $privilege1Field1EditPermission = new AclPermission('EDIT', 5);
        $privilege1Field1->addPermission($privilege1Field1CreatePermission);
        $privilege1Field1->addPermission($privilege1Field1EditPermission);
        $privilege1Field1->addPermission($privilege1Field1ViewPermission);
        $privilege1Field2 = new AclPrivilege();
        $privilege1Field2->setIdentity(new AclPrivilegeIdentity('entity:Acme\Test1Entity::field2', 'field2'));
        $privilege1Field2ViewPermission = new AclPermission('VIEW', 1);
        $privilege1Field2CreatePermission = new AclPermission('CREATE', 2);
        $privilege1Field2EditPermission = new AclPermission('EDIT', 4);
        $privilege1Field2->addPermission($privilege1Field2EditPermission);
        $privilege1Field2->addPermission($privilege1Field2CreatePermission);
        $privilege1Field2->addPermission($privilege1Field2ViewPermission);
        $privilege1->setFields(new ArrayCollection([$privilege1Field1, $privilege1Field2]));
        $privileges = new ArrayCollection(['entity' => new ArrayCollection([$privilege1])]);
        $this->aclRoleHandler->expects($this->any())->method('getAllPrivileges')->willReturn($privileges);

        $category = new PrivilegeCategory('testCategory', 'testCategory', true, 1);
        $this->categoryProvider->expects($this->any())->method('getPermissionCategories')->willReturn([$category]);

        $this->translator->expects($this->any())->method('trans')
            ->willReturnCallback(
                function ($value) {
                    return $value;
                }
            );

        $this->permissionManager->expects($this->any())->method('getPermissionByName')
            ->willReturnCallback(
                function ($permissionName) {
                    $permission = new Permission();
                    $permission->setName($permissionName);
                    $permission->setLabel($permissionName);
                    return $permission;
                }
            );

        /** @var ResultRecord $result */
        $result = $this->datasource->getResults()[0];
        $this->checkResult($result);
    }

    protected function checkResult(ResultRecord $result)
    {
        $this->assertEquals('entity:Acme\Test1Entity', $result->getValue('identity'));
        $this->assertEquals('test entity', $result->getValue('label'));
        $this->assertEquals('testCategory', $result->getValue('group'));
        $this->assertEquals(
            [
                [
                    'id' => null, 'name' => 'VIEW', 'label' => 'VIEW', 'description' => '',
                    'identity' => 'entity:Acme\Test1Entity', 'access_level' => 4,
                    'access_level_label' => 'oro.security.access-level.GLOBAL'
                ],
                [
                    'id' => null, 'name' => 'CREATE', 'label' => 'CREATE', 'description' => '',
                    'identity' => 'entity:Acme\Test1Entity', 'access_level' => 3,
                    'access_level_label' => 'oro.security.access-level.DEEP'
                ],
                [
                    'id' => null, 'name' => 'EDIT', 'label' => 'EDIT', 'description' => '',
                    'identity' => 'entity:Acme\Test1Entity', 'access_level' => 5,
                    'access_level_label' => 'oro.security.access-level.SYSTEM'
                ],
                [
                    'id' => null, 'name' => 'DELETE', 'label' => 'DELETE', 'description' => '',
                    'identity' => 'entity:Acme\Test1Entity', 'access_level' => 0,
                    'access_level_label' => 'oro.security.access-level.NONE'
                ],
                [
                    'id' => null, 'name' => 'CUSTOM', 'label' => 'CUSTOM', 'description' => '',
                    'identity' => 'entity:Acme\Test1Entity', 'access_level' => 1,
                    'access_level_label' => 'oro.security.access-level.BASIC'
                ],
                [
                    'id' => null, 'name' => 'TEST', 'label' => 'TEST', 'description' => '',
                    'identity' => 'entity:Acme\Test1Entity', 'access_level' => 2,
                    'access_level_label' => 'oro.security.access-level.LOCAL'
                ]
            ],
            $result->getValue('permissions')
        );
        $this->assertEquals(
            [
                [
                    'identity' => 'entity:Acme\Test1Entity::field1', 'label' => 'field1',
                    'permissions' => [
                        [
                            'id' => null, 'name' => 'VIEW', 'label' => 'VIEW', 'description' => '',
                            'identity' => 'entity:Acme\Test1Entity::field1', 'access_level' => 3,
                            'access_level_label' => 'oro.security.access-level.DEEP'
                        ],
                        [
                            'id' => null, 'name' => 'CREATE', 'label' => 'CREATE', 'description' => '',
                            'identity' => 'entity:Acme\Test1Entity::field1', 'access_level' => 4,
                            'access_level_label' => 'oro.security.access-level.GLOBAL'
                        ],
                        [
                            'id' => null, 'name' => 'EDIT', 'label' => 'EDIT', 'description' => '',
                            'identity' => 'entity:Acme\Test1Entity::field1', 'access_level' => 5,
                            'access_level_label' => 'oro.security.access-level.SYSTEM'
                        ]
                    ]
                ],
                [
                    'identity' => 'entity:Acme\Test1Entity::field2', 'label' => 'field2',
                    'permissions' => [
                        [
                            'id' => null, 'name' => 'VIEW', 'label' => 'VIEW', 'description' => '',
                            'identity' => 'entity:Acme\Test1Entity::field2', 'access_level' => 1,
                            'access_level_label' => 'oro.security.access-level.BASIC'
                        ],
                        [
                            'id' => null, 'name' => 'CREATE', 'label' => 'CREATE', 'description' => '',
                            'identity' => 'entity:Acme\Test1Entity::field2', 'access_level' => 2,
                            'access_level_label' => 'oro.security.access-level.LOCAL'
                        ],
                        [
                            'id' => null, 'name' => 'EDIT', 'label' => 'EDIT', 'description' => '',
                            'identity' => 'entity:Acme\Test1Entity::field2', 'access_level' => 4,
                            'access_level_label' => 'oro.security.access-level.GLOBAL'
                        ]
                    ]
                ]
            ],
            $result->getValue('fields')
        );
    }
}
