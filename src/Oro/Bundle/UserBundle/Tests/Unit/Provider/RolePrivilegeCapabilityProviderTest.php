<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Extension\ActionAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCapabilityProvider;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;
use Symfony\Component\Translation\TranslatorInterface;

class RolePrivilegeCapabilityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclRoleHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $aclRoleHandler;

    /** @var RolePrivilegeCategoryProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryProvider;

    /** @var RolePrivilegeCapabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $capabilityProvider;

    protected function setUp()
    {
        /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $this->aclRoleHandler = $this->createMock(AclRoleHandler::class);
        $this->categoryProvider = $this->createMock(RolePrivilegeCategoryProvider::class);
        $this->capabilityProvider = new RolePrivilegeCapabilityProvider(
            $translator,
            $this->categoryProvider,
            $this->aclRoleHandler
        );
    }

    /**
     * @dataProvider getCapabilitiesDataProvider
     *
     * @param array $categories
     * @param array $privileges
     * @param array $expected
     */
    public function testGetCapabilities(array $categories, array $privileges, array $expected)
    {
        /** @var AbstractRole||\PHPUnit\Framework\MockObject\MockObject $role */
        $role = $this->createMock(AbstractRole::class);

        $this->categoryProvider
            ->expects($this->once())
            ->method('getPermissionCategories')
            ->willReturn($categories);

        $this->aclRoleHandler->expects($this->once())
            ->method('getAllPrivileges')
            ->with($role)
            ->willReturn($privileges);

        $this->assertEquals($expected, $this->capabilityProvider->getCapabilities($role));
    }

    /**
     * @return array
     */
    public function getCapabilitiesDataProvider()
    {
        $category1 = new PrivilegeCategory('category1', '', true, 1);
        $category2 = new PrivilegeCategory('category2', '', true, 1);

        $permission1 = new AclPermission('permission1');
        $permission2 = new AclPermission('permission2');

        $identityCapability = new AclPrivilegeIdentity(ActionAclExtension::NAME, 'capability1');
        $identityEntity = new AclPrivilegeIdentity(EntityAclExtension::NAME, 'entity1');

        $privilegeCapability = new AclPrivilege();
        $privilegeCapability
            ->addPermission($permission1)
            ->setIdentity($identityCapability)->setCategory($category1->getId());
        $privilegeEntity = new AclPrivilege();
        $privilegeEntity->addPermission($permission2)->setIdentity($identityEntity)->setCategory($category2->getId());


        return [
            'no categories' => [
                'categories' => [],
                'privileges' => [$privilegeCapability, $privilegeEntity],
                'expected' => [],
            ],
            'no privileges' => [
                'categories' => [$category1, $category2],
                 'privileges' => [],
                 'expected' => [],
            ],
            'filled' => [
                'categories' => [$category1, $category2],
                 'privileges' => [
                     'action' => new ArrayCollection([$privilegeCapability]),
                     'entity' => new ArrayCollection([$privilegeEntity])
                 ],
                 'expected' => [
                     [
                        'group' => $privilegeCapability->getCategory(),
                        'label' => null,
                        'items' => [
                            [
                                'id' => ActionAclExtension::NAME,
                                'identity' => ActionAclExtension::NAME,
                                'label' => $identityCapability->getName(),
                                'description' => null,
                                'access_level' => null,
                                'selected_access_level' => AccessLevel::SYSTEM_LEVEL,
                                'unselected_access_level' => AccessLevel::NONE_LEVEL,
                                'name' => $permission1->getName(),
                            ]
                        ],
                     ],
                 ],
            ],
        ];
    }
}
