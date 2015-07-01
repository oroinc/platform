<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormInterface;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\AclRoleType;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;

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
     * @param array $privilegeConfig
     */
    public function __construct(FormFactory $formFactory, array $privilegeConfig)
    {
        $this->formFactory = $formFactory;
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
     * @return FormInterface
     */
    public function createForm(AbstractRole $role)
    {
        foreach ($this->privilegeConfig as $configName => $config) {
            $this->privilegeConfig[$configName]['permissions']
                = $this->privilegeRepository->getPermissionNames($config['types']);
        }

        $this->form = $this->createRoleFormInstance($role, $this->privilegeConfig);

        return $this->form;
    }

    /**
     * @param AbstractRole $role
     * @param array $privilegeConfig
     * @return FormInterface
     */
    protected function createRoleFormInstance(AbstractRole $role, array $privilegeConfig)
    {
        return $this->formFactory->create(
            new ACLRoleType($privilegeConfig),
            $role
        );
    }

    /**
     * Save role
     *
     * @param AbstractRole $role
     * @return bool
     */
    public function process(AbstractRole $role)
    {
        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $appendUsers = $this->form->get('appendUsers')->getData();
                $removeUsers = $this->form->get('removeUsers')->getData();
                $this->onSuccess($role, $appendUsers, $removeUsers);
                $this->processPrivileges($role);

                return true;
            }
        } else {
            $this->setRolePrivileges($role);
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
     */
    protected function setRolePrivileges(AbstractRole $role)
    {
        /** @var ArrayCollection $privileges */
        $privileges = $this->privilegeRepository->getPrivileges($this->aclManager->getSid($role));

        foreach ($this->privilegeConfig as $fieldName => $config) {
            $sortedPrivileges = $this->filterPrivileges($privileges, $config['types']);
            if ($config['fix_values'] || !$config['show_default']) {
                foreach ($sortedPrivileges as $sortedPrivilege) {
                    if (!$config['show_default']
                        && $sortedPrivilege->getIdentity()->getName() == AclPrivilegeRepository::ROOT_PRIVILEGE_NAME) {
                        $sortedPrivileges->removeElement($sortedPrivilege);
                        continue;
                    }
                    if ($config['fix_values']) {
                        foreach ($sortedPrivilege->getPermissions() as $permission) {
                            $permission->setAccessLevel((bool)$permission->getAccessLevel());
                        }
                    }
                }
            }

            $this->form->get($fieldName)->setData($sortedPrivileges);
        }
    }

    /**
     * @param AbstractRole $role
     */
    protected function processPrivileges(AbstractRole $role)
    {
        $formPrivileges = array();
        foreach ($this->privilegeConfig as $fieldName => $config) {
            $privileges = $this->form->get($fieldName)->getData();
            if ($config['fix_values']) {
                $this->fxPrivilegeValue($privileges, $config['default_value']);
            }
            $formPrivileges = array_merge($formPrivileges, $privileges);
        }

        $this->privilegeRepository->savePrivileges(
            $this->aclManager->getSid($role),
            new ArrayCollection($formPrivileges)
        );
    }

    /**
     * @param ArrayCollection $privileges
     * @param array $rootIds
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
}
