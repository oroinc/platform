<?php


namespace Oro\Bundle\UserBundle\Datagrid;


use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Symfony\Component\Translation\TranslatorInterface;

class RepositoryDatasource implements DatasourceInterface
{
    private $translator;
    private $aclPrivilegeRepository;
    private $permissionManager;
    private $aclManager;
    private $role;


    public function __construct(
        TranslatorInterface $translator,
        AclPrivilegeRepository $aclPrivilegeRepository, 
        PermissionManager $permissionManager,
        AclManager $aclManager
    ){
        $this->translator = $translator;
        $this->aclPrivilegeRepository = $aclPrivilegeRepository;
        $this->permissionManager = $permissionManager;
        $this->aclManager = $aclManager;
    }

    public function process(DatagridInterface $grid, array $config)
    {
        $this->role = $grid->getParameters()->get('role');
        // TODO: Implement process() method.
        $grid->setDatasource(/* clone */$this);
    }

    /**
     * @return ResultRecordInterface[]
     */
    public function getResults()
    {
        $translator = $this->translator;
        $permissionManager = $this->permissionManager;
        $gridData = [];
        $allPrivileges = $this->aclPrivilegeRepository->getPrivileges($this->aclManager->getSid($this->role));

        foreach ($allPrivileges as $key => $privilege) {
            /** @var AclPrivilege $privilege */
            $item = [
                    'identity' => $privilege->getIdentity()->getId(),
                    'entity' => $translator->trans($privilege->getIdentity()->getName()),
                    'group' => ['account_management', 'marketing', 'sales_data', null][count($gridData) % 4],
                    'permissions' => [],
            ];

//            if (strpos($oid, 'entity:') === 0) {
//                $entityClass = substr($oid, 7);
//                if ($entityClass !== ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
//                    $entityClass = $this->get('oro_entity.routing_helper')->resolveEntityClass($entityClass);
//                    $config = $configEntityManager->getConfig($entityClass);
//                    if ($config->has('category')) {
//                        $item['group'] = $config->get('category');
//                    }
//                }
//            }
            
            foreach ($privilege->getPermissions() as $permissionName => $permission) {
                $permissionEntity = $permissionManager->getPermissionByName($permission->getName());
                if (!empty($permissionEntity)) {
                    /** @var AclPermission $permission */
                    $permissionLabel = $permissionEntity->getLabel() ? $permissionEntity->getLabel() : $permissionName;
                    $permissionDescription = '';
                    if ($permissionEntity->getDescription()) {
                        $permissionDescription = $translator->trans($permissionEntity->getDescription());
                    }
                    $accessLevelVars = $permission->getAccessLevel();
//                $valueText = $accessLevelVars['translation_prefix'] .
//                    (empty($accessLevelVars['level_label']) ? 'NONE' : $accessLevelVars['level_label']);
//                $valueText = $translator->trans($valueText, [], $accessLevelVars['translation_domain']);

                    $item['permissions'][] = [
                        'id' => $permissionEntity->getId(),
                        'name' => $permissionEntity->getName(),
                        'label' => $permissionLabel,
                        'description' => $permissionDescription,
                        'identity' => $permissionEntity->getId(),
                        'access_level' => $accessLevelVars,
                        'access_level_label' => $accessLevelVars
                    ];
                }
            }
            $gridData[] = new ResultRecord($item);
        }
       
        return $gridData;
    }
}