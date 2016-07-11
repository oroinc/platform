<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\AclRoleType;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\SecurityBundle\Acl\Group\AclGroupProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;

/**
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
     * @var ObjectManager
     *
     * @deprecated since 1.8
     */
    protected $manager;

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

    /**
     * @param FormFactory $formFactory
     * @param AclCacheInterface $aclCache
     * @param array $privilegeConfig
     */
    public function __construct(FormFactory $formFactory, AclCacheInterface $aclCache, array $privilegeConfig)
    {
        $this->formFactory = $formFactory;
        $this->aclCache = $aclCache;
        $this->privilegeConfig = $privilegeConfig;
    }

    /**
     * @param AclManager $aclManager
     */
    public function setAclManager(AclManager $aclManager)
    {
        $this->aclManager = $aclManager;
    }

    /**
     * @param AclPrivilegeRepository $privilegeRepository
     */
    public function setAclPrivilegeRepository(AclPrivilegeRepository $privilegeRepository)
    {
        $this->privilegeRepository = $privilegeRepository;
    }

    /**
     * @param ManagerRegistry $registry
     */
    public function setManagerRegistry(ManagerRegistry $registry)
    {
        $this->managerRegistry = $registry;
    }

    /**
     * @param ObjectManager $manager
     *
     * @deprecated since 1.8
     */
    public function setEntityManager(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
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
     * @param null|string  $className
     *
     * @return FormInterface
     */
    public function createForm(AbstractRole $role, $className = null)
    {
        $this->loadPrivilegeConfigPermissions($className);

        $this->form = $this->createRoleFormInstance($role, $this->privilegeConfig);

        return $this->form;
    }

    /**
     * @param string $className
     */
    protected function loadPrivilegeConfigPermissions($className = null)
    {
        foreach ($this->privilegeConfig as $configName => $config) {
            $this->privilegeConfig[$configName]['permissions']
                = $this->privilegeRepository->getPermissionNames($config['types']);
        }

        if ($className) {
            // leave only fields privileges config
            $this->privilegeConfig = array_intersect_key($this->privilegeConfig, array_flip(['field']));
        } else {
            // unset field privileges config
            unset($this->privilegeConfig['field']);
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
            new AclRoleType($privilegeConfig),
            $role
        );
    }

    /**
     * Save role
     *
     * @param AbstractRole $role
     * @param null|string  $className
     *
     * @return bool
     */
    public function process(AbstractRole $role, $className = null)
    {
        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $data = $this->request->request->get($this->form->getName(), []);
            if (isset($data['privileges'])) {
                $privileges = json_decode($data['privileges'], true);
                if (is_array($privileges)) {
                    $data = array_merge($data, $privileges);
                }
            }
            $this->form->submit($data);
            if ($this->form->isValid()) {
                $appendUsers = $this->form->get('appendUsers')->getData();
                $removeUsers = $this->form->get('removeUsers')->getData();
                $this->onSuccess($role, $appendUsers, $removeUsers);
                $this->processPrivileges($role, $className);

                return true;
            }
        } else {
            $formPrivileges = $this->prepareRolePrivilegies($role);
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
     */
    public function getAllPrivileges(AbstractRole $role, $className = null)
    {
        $allPrivileges = array();
        $privileges = $this->getRolePrivileges($role, $className);

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
    protected function prepareRolePrivilegies(AbstractRole $role)
    {
        $allPrivileges = [];
        /**
         * @var string $fieldName
         * @var ArrayCollection|AclPrivilege[] $sortedPrivileges
         */
        foreach ($this->getAllPrivileges($role) as $fieldName => $sortedPrivileges) {
            $this->form->get($fieldName)->setData($sortedPrivileges);
            $allPrivileges = array_merge($allPrivileges, $sortedPrivileges->toArray());
        }

        return $this->getFormPrivileges($allPrivileges);
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
     * @param null|string  $className
     *
     * @return ArrayCollection|\Oro\Bundle\SecurityBundle\Model\AclPrivilege[]
     */
    protected function getRolePrivileges(AbstractRole $role, $className = null)
    {
        $securityIdentity = $this->aclManager->getSid($role);
        if ($className) {
            return $this->privilegeRepository->getFieldsPrivileges($securityIdentity, $className);
        }

        return $this->privilegeRepository->getPrivileges($securityIdentity);
    }

    /**
     * @param AbstractRole $role
     * @param null|string  $className
     */
    protected function processPrivileges(AbstractRole $role, $className = null)
    {
        $decodedPrivileges = json_decode($this->form->get('privileges')->getData(), true);
        $formPrivileges = [];
        foreach ($this->privilegeConfig as $fieldName => $config) {
            $privilegesArray = $decodedPrivileges[$fieldName];
            $privileges = [];
            foreach ($privilegesArray as $privilege) {
                $aclPrivilege = new AclPrivilege();
                foreach ($privilege['permissions'] as $name => $permission) {
                    $aclPrivilege->addPermission(new AclPermission($permission['name'], $permission['accessLevel']));
                }
                $aclPrivilegeIdentity = new AclPrivilegeIdentity(
                    $privilege['identity']['id'],
                    $privilege['identity']['name']
                );
                $aclPrivilege->setIdentity($aclPrivilegeIdentity);
                $privileges[] = $aclPrivilege;
            }
            if ($config['fix_values']) {
                $this->fxPrivilegeValue($privileges, $config['default_value']);
            }
            $formPrivileges = array_merge($formPrivileges, $privileges);
        }

        array_walk(
            $formPrivileges,
            function (AclPrivilege $privilege) {
                $privilege->setGroup($this->getAclGroup());
            }
        );

        if ($className) {
            $this->privilegeRepository->saveFieldPrivileges(
                $this->aclManager->getSid($role),
                new ObjectIdentity('field', $className),
                new ArrayCollection($formPrivileges)
            );
        } else {
            $this->privilegeRepository->savePrivileges(
                $this->aclManager->getSid($role),
                new ArrayCollection($formPrivileges)
            );
        }

        $this->aclCache->clearCache();
    }

    /**
     * @param ArrayCollection $privileges
     * @param array           $rootIds
     *
     * @return ArrayCollection|AclPrivilege[]
     */
    protected function filterPrivileges(ArrayCollection $privileges, array $rootIds)
    {
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
            $user->addRole($role);
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
            $user->removeRole($role);
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
     * @param $allPrivileges
     *
     * @return array
     */
    protected function getFormPrivileges($allPrivileges)
    {
        $formPrivileges = [];
        foreach ($allPrivileges as $key => $privilege) {
            /** @var AclPrivilege $privilege */
            $result = [
                'identity'    => [
                    'id'   => $privilege->getIdentity()->getId(),
                    'name' => $privilege->getIdentity()->getName(),
                ],
                'permissions' => [],
            ];
            foreach ($privilege->getPermissions() as $permissionName => $permission) {
                /** @var AclPermission $permission */
                $result['permissions'][$permissionName] = [
                    'name'        => $permission->getName(),
                    'accessLevel' => $permission->getAccessLevel(),
                ];
            }
            $formPrivileges[$privilege->getExtensionKey()][$key] = $result;
        }

        return $formPrivileges;
    }
}
