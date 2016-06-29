<?php

namespace Oro\Bundle\UserBundle\Datagrid;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\UserBundle\Provider\RolePrivilegeAbstractProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Form\Type\AclAccessLevelSelectorType;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Bundle\UserBundle\Entity\Role;

class RolePermissionDatasource extends RolePrivilegeAbstractProvider implements DatasourceInterface
{
    /** @var PermissionManager */
    protected $permissionManager;

    /** @var ConfigManager */
    protected $configEntityManager;

    /** @var Role */
    protected $role;

    /** @var string[] Exclude share permissions in platform application because it is enterprise feature */
    protected static $excludePermissions = ['SHARE'];

    /**
     * RolePermissionDatasource constructor.
     *
     * @param TranslatorInterface           $translator
     * @param PermissionManager             $permissionManager
     * @param AclRoleHandler                $aclRoleHandler
     * @param RolePrivilegeCategoryProvider $categoryProvider
     * @param ConfigManager                 $configEntityManager
     */
    public function __construct(
        TranslatorInterface $translator,
        PermissionManager $permissionManager,
        AclRoleHandler $aclRoleHandler,
        RolePrivilegeCategoryProvider $categoryProvider,
        ConfigManager $configEntityManager
    ) {
        parent::__construct($translator, $categoryProvider, $aclRoleHandler);
        $this->permissionManager = $permissionManager;
        $this->configEntityManager = $configEntityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(DatagridInterface $grid, array $config)
    {
        $this->role = $grid->getParameters()->get('role');
        $grid->setDatasource(clone $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getResults()
    {
        $gridData = [];
        $allPrivileges = $this->preparePrivileges($this->role, 'entity');
        $categories = $this->categoryProvider->getPermissionCategories();

        foreach ($allPrivileges as $key => $privilege) {
            /** @var AclPrivilege $privilege */
            $item = [
                'identity' => $privilege->getIdentity()->getId(),
                'entity' => $this->translator->trans($privilege->getIdentity()->getName()),
                'group' => $this->getPrivilegeCategory($privilege, $categories),
                'permissions' => [],
            ];
            $item = $this->preparePermissions($privilege, $item);
            $gridData[] = new ResultRecord($item);
        }

        return $gridData;
    }

    /**
     * @param AclPrivilege $privilege
     * @param array $item
     *
     * @return mixed
     */
    protected function preparePermissions(AclPrivilege $privilege, $item)
    {
        $orders = [];
        foreach ($privilege->getPermissions() as $permissionName => $permission) {
            /** @var AclPermission $permission */
            $permissionEntity = $this->permissionManager->getPermissionByName($permission->getName());
            if ($permissionEntity && $this->isSupportedPermission($permissionName)) {
                $privilegePermission = $this->getPrivilegePermission(
                    $privilege,
                    $permissionEntity,
                    $permissionName,
                    $permission
                );
                $item['permissions'][] = $privilegePermission;
                $orders[] = $privilegePermission['label'];
            }
        }
        array_multisort($orders, $item['permissions']);

        return $item;
    }

    /**
     * Filter some permissions like SHARE in platform. Should be fixed by - CRM-5781
     * Should be fixed in PermissionCollectionType too.
     *
     * @param string $permissionName
     *
     * @return bool
     */
    protected function isSupportedPermission($permissionName)
    {
        return !in_array($permissionName, static::$excludePermissions, true);
    }

    /**
     * @param AclPrivilege $privilege
     * @param Permission $permissionEntity
     * @param string $permissionName
     * @param AclPermission $permission
     *
     * @return array
     */
    protected function getPrivilegePermission(
        AclPrivilege $privilege,
        Permission $permissionEntity,
        $permissionName,
        AclPermission $permission
    ) {
        $permissionLabel = $permissionEntity->getLabel() ? $permissionEntity->getLabel() : $permissionName;
        $permissionLabel = $this->translator->trans($permissionLabel);

        $permissionDescription = '';
        if ($permissionEntity->getDescription()) {
            $permissionDescription = $this->translator->trans($permissionEntity->getDescription());
        }

        $accessLevel = $permission->getAccessLevel();
        $accessLevelName = AccessLevel::getAccessLevelName($accessLevel);
        $valueText = AclAccessLevelSelectorType::TRANSLATE_KEY_ACCESS_LEVEL . '.'
            . (empty($accessLevelName) ? 'NONE' : $accessLevelName);
        $valueText = $this->translator->trans($valueText);

        return [
            'id'                 => $permissionEntity->getId(),
            'name'               => $permissionEntity->getName(),
            'label'              => $permissionLabel,
            'description'        => $permissionDescription,
            'identity'           => $privilege->getIdentity()->getId(),
            'access_level'       => $accessLevel,
            'access_level_label' => $valueText
        ];
    }
}
