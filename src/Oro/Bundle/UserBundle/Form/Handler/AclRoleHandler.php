<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\XcacheCache;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Permission\ConfigurablePermissionProvider;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;
use Oro\Bundle\SecurityBundle\Filter\AclPrivilegeConfigurableFilter;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\AclRoleType;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;

/**
 * Handler that saves role data with privileges to db.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AclRoleHandler
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var AclManager
     */
    protected $aclManager;

    /**
     * @var AclPrivilegeRepository
     */
    protected $privilegeRepository;

    /**
     * @var AclCacheInterface
     */
    protected $aclCache;

    /**
     * @var array
     */
    protected $privilegeConfig;

    /**
     * ['<extension_key>' => ['<allowed_group>', ...], ...]
     *
     * @var array
     */
    protected $extensionFilters = [];

    /** @var string */
    protected $configurableName;

    /** @var AclPrivilegeConfigurableFilter */
    protected $configurableFilter;

    public function __construct(FormFactory $formFactory, AclCacheInterface $aclCache, array $privilegeConfig)
    {
        $this->formFactory = $formFactory;
        $this->aclCache = $aclCache;
        $this->privilegeConfig = $privilegeConfig;
        $this->configurableName = ConfigurablePermissionProvider::DEFAULT_CONFIGURABLE_NAME;
    }

    public function setAclManager(AclManager $aclManager)
    {
        $this->aclManager = $aclManager;
    }

    public function setAclPrivilegeRepository(AclPrivilegeRepository $privilegeRepository)
    {
        $this->privilegeRepository = $privilegeRepository;
    }

    public function setManagerRegistry(ManagerRegistry $registry)
    {
        $this->managerRegistry = $registry;
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param string $configurableName
     */
    public function setConfigurableName($configurableName)
    {
        $this->configurableName = $configurableName;
    }

    public function setConfigurableFilter(AclPrivilegeConfigurableFilter $configurableFilter)
    {
        $this->configurableFilter = $configurableFilter;
    }

    /**
     * @param string $extensionKey
     * @param string $allowedGroup
     */
    public function addExtensionFilter($extensionKey, $allowedGroup)
    {
        if (!array_key_exists($extensionKey, $this->extensionFilters)) {
            $this->extensionFilters[$extensionKey] = [];
        }

        if (!in_array($allowedGroup, $this->extensionFilters[$extensionKey])) {
            $this->extensionFilters[$extensionKey][] = $allowedGroup;
        }
    }

    /**
     * Create form for role manipulation
     *
     * @param AbstractRole $role
     *
     * @return FormInterface
     */
    public function createForm(AbstractRole $role)
    {
        $this->loadPrivilegeConfigPermissions();

        $this->form = $this->createRoleFormInstance($role, $this->privilegeConfig);

        return $this->form;
    }

    /**
     * Load privilege config permissions
     */
    protected function loadPrivilegeConfigPermissions()
    {
        foreach ($this->privilegeConfig as $configName => $config) {
            $this->privilegeConfig[$configName]['permissions']
                = $this->privilegeRepository->getPermissionNames($config['types']);
        }
    }

    /**
     * @param AbstractRole $role
     * @param array $privilegeConfig
     * @return FormInterface
     */
    protected function createRoleFormInstance(AbstractRole $role, array $privilegeConfig)
    {
        return $this->formFactory->create(
            AclRoleType::class,
            $role
        );
    }

    /**
     * Save role
     *
     * @param AbstractRole $role
     *
     * @return bool
     */
    public function process(AbstractRole $role)
    {
        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $data = $this->request->request->get($this->form->getName(), []);
            $this->form->submit($data);
            if ($this->form->isValid()) {
                $appendUsers = $this->form->get('appendUsers')->getData();
                $removeUsers = $this->form->get('removeUsers')->getData();
                $this->onSuccess($role, $appendUsers, $removeUsers);
                $this->processPrivileges($role);

                return true;
            }
        } else {
            $formPrivileges = $this->prepareRolePrivileges($role);
            $this->form->get('privileges')->setData(json_encode($formPrivileges));
        }

        return false;
    }

    /**
     * Create form view for current form
     *
     * @return \Symfony\Component\Form\FormView
     */
    public function createView()
    {
        return $this->form->createView();
    }

    /**
     * @param AbstractRole $role
     *
     * @return array
     *   key - privilege type (entity, action)
     *   value - ArrayCollection of AclPrivilege data
     */
    public function getAllPrivileges(AbstractRole $role)
    {
        $allPrivileges = [];
        $privileges = $this->getRolePrivileges($role);

        foreach ($this->privilegeConfig as $fieldName => $config) {
            $sortedPrivileges = $this->filterPrivileges($privileges, $config['types']);
            $this->applyOptions($sortedPrivileges, $config);
            $allPrivileges[$fieldName] = $sortedPrivileges;
        }

        return $allPrivileges;
    }

    /**
     * @param AbstractRole $role
     *
     * @return array
     */
    protected function prepareRolePrivileges(AbstractRole $role)
    {
        $allPrivileges = [];
        /**
         * @var string $fieldName
         * @var ArrayCollection|AclPrivilege[] $sortedPrivileges
         */
        foreach ($this->getAllPrivileges($role) as $fieldName => $sortedPrivileges) {
            $allPrivileges = array_merge($allPrivileges, $sortedPrivileges->toArray());
        }

        return $this->encodeAclPrivileges($allPrivileges);
    }

    /**
     * @param ArrayCollection|AclPrivilege[] $sortedPrivileges
     * @param array $config
     */
    protected function applyOptions(ArrayCollection $sortedPrivileges, array $config)
    {
        $hideDefault = !$config['show_default'];
        $fixValues = $config['fix_values'];

        if ($fixValues || $hideDefault) {
            foreach ($sortedPrivileges as $sortedPrivilege) {
                if ($hideDefault
                    && $sortedPrivilege->getIdentity()->getName() === AclPrivilegeRepository::ROOT_PRIVILEGE_NAME
                ) {
                    $sortedPrivileges->removeElement($sortedPrivilege);
                    continue;
                }

                if ($fixValues) {
                    foreach ($sortedPrivilege->getPermissions() as $permission) {
                        $permission->setAccessLevel((bool)$permission->getAccessLevel());
                    }
                }
            }
        }
    }

    /**
     * @param AbstractRole $role
     *
     * @return ArrayCollection|AclPrivilege[]
     */
    protected function getRolePrivileges(AbstractRole $role)
    {
        return $this->privilegeRepository->getPrivileges($this->aclManager->getSid($role), $this->getAclGroup());
    }

    protected function processPrivileges(AbstractRole $role)
    {
        $decodedPrivileges = json_decode($this->form->get('privileges')->getData(), true);
        $formPrivileges = [];
        foreach ($this->privilegeConfig as $fieldName => $config) {
            if (array_key_exists($fieldName, $decodedPrivileges)) {
                $privilegesArray = $decodedPrivileges[$fieldName];
                $formPrivileges = array_merge($formPrivileges, $this->decodeAclPrivileges($privilegesArray, $config));
            }
        }

        array_walk(
            $formPrivileges,
            function (AclPrivilege $privilege) {
                $privilege->setGroup($this->getAclGroup());
            }
        );

        $this->privilegeRepository->savePrivileges(
            $this->aclManager->getSid($role),
            $this->configurableFilter->filter(new ArrayCollection($formPrivileges), $this->configurableName)
        );

        $this->clearAclCache($role);
    }

    protected function clearAclCache(AbstractRole $role): void
    {
        $this->aclCache->clearCache();

        // Clear doctrine query cache to be sure that queries will process hints
        // again with updated security information.
        $cacheDriver = $this->managerRegistry->getManager()->getConfiguration()->getQueryCacheImpl();
        if ($cacheDriver && !($cacheDriver instanceof ApcCache && $cacheDriver instanceof XcacheCache)) {
            $cacheDriver->deleteAll();
        }
    }

    /**
     * @param ArrayCollection $privileges
     * @param array           $rootIds
     *
     * @return ArrayCollection|AclPrivilege[]
     */
    protected function filterPrivileges(ArrayCollection $privileges, array $rootIds)
    {
        $privileges = $this->configurableFilter->filter($privileges, $this->configurableName);

        return $privileges->filter(
            function (AclPrivilege $entry) use ($rootIds) {
                $extensionKey = $entry->getExtensionKey();

                // only current extension privileges
                if (!in_array($extensionKey, $rootIds, true)) {
                    return false;
                }

                // not filtered are allowed
                if (!array_key_exists($extensionKey, $this->extensionFilters)) {
                    return true;
                }

                // filter by groups
                return in_array($entry->getGroup(), $this->extensionFilters[$extensionKey], true);
            }
        );
    }

    /**
     * @param ArrayCollection|AclPrivilege[] $privileges
     * @param $value
     */
    protected function fxPrivilegeValue($privileges, $value)
    {
        foreach ($privileges as $privilege) {
            foreach ($privilege->getPermissions() as $permission) {
                $permission->setAccessLevel($permission->getAccessLevel() ? $value : 0);
            }
        }
    }

    /**
     * "Success" form handler
     *
     * @param AbstractRole $entity
     * @param User[] $appendUsers
     * @param User[] $removeUsers
     */
    protected function onSuccess(AbstractRole $entity, array $appendUsers, array $removeUsers)
    {
        $manager = $this->getManager($entity);

        $this->appendUsers($entity, $appendUsers);
        $this->removeUsers($entity, $removeUsers);
        $manager->persist($entity);
        $manager->flush();
    }

    /**
     * Append users to role
     *
     * @param AbstractRole $role
     * @param User[] $users
     */
    protected function appendUsers(AbstractRole $role, array $users)
    {
        $manager = $this->getManager($role);

        /** @var $user AbstractUser */
        foreach ($users as $user) {
            $user->addUserRole($role);
            $manager->persist($user);
        }
    }

    /**
     * Remove users from role
     *
     * @param AbstractRole $role
     * @param User[] $users
     */
    protected function removeUsers(AbstractRole $role, array $users)
    {
        $manager = $this->getManager($role);

        /** @var $user AbstractUser */
        foreach ($users as $user) {
            $user->removeUserRole($role);
            $manager->persist($user);
        }
    }

    /**
     * @param AbstractRole $role
     * @return ObjectManager
     */
    protected function getManager(AbstractRole $role)
    {
        return $this->managerRegistry->getManagerForClass(ClassUtils::getClass($role));
    }

    /**
     * @return string
     */
    protected function getAclGroup()
    {
        return AclGroupProviderInterface::DEFAULT_SECURITY_GROUP;
    }

    /**
     * Encode array of AclPrivilege objects into array of plain privileges
     *
     * @param array $allPrivileges
     * @param bool $addExtensionName
     *
     * @return array
     */
    protected function encodeAclPrivileges($allPrivileges, $addExtensionName = true)
    {
        $formPrivileges = [];
        if (!$allPrivileges) {
            return $formPrivileges;
        }
        foreach ($allPrivileges as $key => $privilege) {
            /** @var AclPrivilege $privilege */
            $result = [
                'identity'    => [
                    'id'   => $privilege->getIdentity()->getId(),
                    'name' => $privilege->getIdentity()->getName(),
                ],
                'permissions' => [],
            ];
            $fields = $this->encodeAclPrivileges($privilege->getFields(), false);
            if ($fields) {
                $result['fields'] = $fields;
            }
            foreach ($privilege->getPermissions() as $permissionName => $permission) {
                /** @var AclPermission $permission */
                $result['permissions'][$permissionName] = [
                    'name'        => $permission->getName(),
                    'accessLevel' => $permission->getAccessLevel(),
                ];
            }
            $addExtensionName
                ? $formPrivileges[$privilege->getExtensionKey()][$key] = $result
                : $formPrivileges[$key] = $result;
        }

        return $formPrivileges;
    }

    /**
     * Decode array of plain privileges info into array of AclPrivilege objects
     *
     * @param array $privilegesArray
     * @param array $config
     *
     * @return array|AclPrivilege[]
     */
    protected function decodeAclPrivileges($privilegesArray, $config)
    {
        $privileges = [];
        foreach ($privilegesArray as $privilege) {
            $aclPrivilege = new AclPrivilege();
            foreach ($privilege['permissions'] as $permission) {
                $aclPrivilege->addPermission(new AclPermission($permission['name'], $permission['accessLevel']));
            }
            $aclPrivilegeIdentity = new AclPrivilegeIdentity(
                $privilege['identity']['id'],
                $privilege['identity']['name']
            );
            $aclPrivilege->setIdentity($aclPrivilegeIdentity);
            if (isset($privilege['fields']) && count($privilege['fields'])) {
                $aclPrivilege->setFields(
                    new ArrayCollection($this->decodeAclPrivileges($privilege['fields'], $config))
                );
            }
            $privileges[] = $aclPrivilege;
        }
        if ($config['fix_values']) {
            $this->fxPrivilegeValue($privileges, $config['default_value']);
        }

        return $privileges;
    }
}
