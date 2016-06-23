<?php

namespace Oro\Bundle\UserBundle\Datagrid;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Provider\RolePermissionCategoryProvider;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Form\Type\AclAccessLevelSelectorType;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Bundle\UserBundle\Entity\Role;

class RolePermissionDatasource implements DatasourceInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var PermissionManager */
    protected $permissionManager;

    /** @var RolePermissionCategoryProvider */
    protected $categoryProvider;

    /** @var AclRoleHandler */
    protected $aclRoleHandler;

    /** @var ConfigManager */
    protected $configEntityManager;

    /** @var Role */
    protected $role;

    /**
     * RolePermissionDatasource constructor.
     *
     * @param TranslatorInterface            $translator
     * @param PermissionManager              $permissionManager
     * @param AclRoleHandler                 $aclRoleHandler
     * @param RolePermissionCategoryProvider $categoryProvider
     * @param ConfigManager                  $configEntityManager
     */
    public function __construct(
        TranslatorInterface $translator,
        PermissionManager $permissionManager,
        AclRoleHandler $aclRoleHandler,
        RolePermissionCategoryProvider $categoryProvider,
        ConfigManager $configEntityManager
    ) {
        $this->translator = $translator;
        $this->permissionManager = $permissionManager;
        $this->aclRoleHandler = $aclRoleHandler;
        $this->categoryProvider = $categoryProvider;
        $this->configEntityManager = $configEntityManager;
    }

    public function process(DatagridInterface $grid, array $config)
    {
        $this->role = $grid->getParameters()->get('role');
        $grid->setDatasource(clone $this);
    }

    /**
     * @return ResultRecordInterface[]
     */
    public function getResults()
    {
        $gridData = [];
        $allPrivileges = $this->preparePriveleges($this->role);

        foreach ($allPrivileges as $key => $privilege) {
            /** @var AclPrivilege $privilege */
            $item = [
                'identity' => $privilege->getIdentity()->getId(),
                'entity' => $this->translator->trans($privilege->getIdentity()->getName()),
                'group' => RolePermissionCategoryProvider::DEFAULT_ENTITY_CATEGORY,
                'permissions' => [],
            ];
            $item['group'] = $this->getEntityCategory($privilege->getIdentity()->getId());
            $item = $this->preparePermissions($privilege, $item);
            $gridData[] = new ResultRecord($item);
        }

        return $gridData;
    }

    /**
     * @param Role $role
     *
     * @return array
     */
    protected function preparePriveleges(Role $role)
    {
        $allPrivileges = [];
        /**
         * @var string $type
         * @var ArrayCollection $sortedPrivileges
         */
        foreach ($this->aclRoleHandler->getAllPriveleges($role) as $type => $sortedPrivileges) {
            if ($type === 'entity') {
                $allPrivileges = array_merge($allPrivileges, $sortedPrivileges->toArray());
            }
        }

        return $allPrivileges;
    }

    /**
     * @param AclPrivilege $privilege
     * @param array $item
     *
     * @return mixed
     */
    protected function preparePermissions(AclPrivilege $privilege, $item)
    {
        foreach ($privilege->getPermissions() as $permissionName => $permission) {
            /** @var AclPermission $permission */
            $permissionEntity = $this->permissionManager->getPermissionByName($permission->getName());
            if ($permissionEntity) {
                $item['permissions'][] = $this->setPrivelegePermission(
                    $privilege,
                    $permissionEntity,
                    $permissionName,
                    $permission
                );

            }
        }

        return $item;
    }

    /**
     * @param string $oid
     *
     * @return mixed|null
     */
    protected function getEntityCategory($oid)
    {
        $entityPrefix =  'entity:';

        if (strpos($oid, $entityPrefix) === 0) {
            $entityClass = substr($oid, 7);
            if ($entityClass !== ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
                if ($this->configEntityManager->hasConfig($entityClass)) {
                    $config = $this->configEntityManager->getProvider('entity')->getConfig($entityClass);
                    if ($config->has('category')) {
                        return $config->get('category');
                    }
                }
            }
        }

        return RolePermissionCategoryProvider::DEFAULT_ENTITY_CATEGORY;
    }

    /**
     * @param AclPrivilege$privilege
     * @param Permission $permissionEntity
     * @param string $permissionName
     * @param AclPermission $permission
     *
     * @return array
     */
    protected function setPrivelegePermission(
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
