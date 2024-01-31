<?php

namespace Oro\Bundle\UserBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClient;
use Oro\Bundle\UIBundle\Route\Router;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCapabilityProvider;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This controller covers CRUD functionality for Role entity.
 * @Route("/role")
 */
class RoleController extends AbstractController
{
    /**
     * @Acl(
     *      id="oro_user_role_create",
     *      type="entity",
     *      class="Oro\Bundle\UserBundle\Entity\Role",
     *      permission="CREATE"
     * )
     * @Route("/create", name="oro_user_role_create")
     * @Template("@OroUser/Role/update.html.twig")
     */
    public function createAction(Request $request)
    {
        return $this->update(new Role(), $request);
    }

    /**
     * @Route("/view/{id}", name="oro_user_role_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_user_role_view",
     *      type="entity",
     *      class="Oro\Bundle\UserBundle\Entity\Role",
     *      permission="VIEW"
     * )
     *
     * @param Role $role
     *
     * @return array
     */
    public function viewAction(Role $role)
    {
        return [
            'entity' => $role,
            'entity_class' => ClassUtils::getRealClass($role),
            'tabsOptions' => [
                'data' => $this->getRolePrivilegeCategoryProvider()->getTabs()
            ],
            'capabilitySetOptions' => [
                'data' => $this->getRolePrivilegeCapabilityProvider()->getCapabilities($role),
                'tabIds' => $this->getRolePrivilegeCategoryProvider()->getTabIds(),
                'readonly' => true
            ],
            'allow_delete' => $role->getId() && !$this->hasAssignedUsers($role)
        ];
    }

    /**
     * @Acl(
     *      id="oro_user_role_update",
     *      type="entity",
     *      class="Oro\Bundle\UserBundle\Entity\Role",
     *      permission="EDIT"
     * )
     * @Route("/update/{id}", name="oro_user_role_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template
     *
     * @param Role $entity
     *
     * @return array
     */
    public function updateAction(Role $entity, Request $request)
    {
        return $this->update($entity, $request);
    }

    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_user_role_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Acl(
     *      id="oro_user_role_view",
     *      type="entity",
     *      class="Oro\Bundle\UserBundle\Entity\Role",
     *      permission="VIEW"
     * )
     * @Template
     */
    public function indexAction()
    {
        return [
            'entity_class' => Role::class
        ];
    }

    /**
     * @param Role $role
     * @param Request $request
     *
     * @return array
     */
    protected function update(Role $role, Request $request)
    {
        /** @var AclRoleHandler $aclRoleHandler */
        $aclRoleHandler = $this->container->get(AclRoleHandler::class);
        $aclRoleHandler->createForm($role);

        if ($aclRoleHandler->process($role)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('oro.user.controller.role.message.saved')
            );

            if ($this->container->get(ConnectionChecker::class)->checkConnection()) {
                $publisher = $this->container->get(WebsocketClient::class);
                $publisher->publish('oro/outdated_user_page', ['role' => $role->getRole()]);
            }

            return $this->container->get(Router::class)->redirect($role);
        }

        $form = $aclRoleHandler->createView();
        $tabs = $this->getRolePrivilegeCategoryProvider()->getTabs();

        return [
            'entity' => $role,
            'form' => $form,
            'tabsOptions' => [
                'data' => $tabs
            ],
            'capabilitySetOptions' => $this->getRolePrivilegeCapabilityProvider()->getCapabilitySetOptions($role),
            'privilegesConfig' => $this->getParameter('oro_user.privileges'),
            'allow_delete' => $role->getId() && !$this->hasAssignedUsers($role)
        ];
    }

    protected function getRolePrivilegeCategoryProvider(): RolePrivilegeCategoryProvider
    {
        return $this->container->get(RolePrivilegeCategoryProvider::class);
    }

    protected function getRolePrivilegeCapabilityProvider(): RolePrivilegeCapabilityProvider
    {
        return $this->container->get(RolePrivilegeCapabilityProvider::class);
    }

    protected function hasAssignedUsers(Role $role): bool
    {
        return $this->container->get(ManagerRegistry::class)->getManagerForClass(Role::class)
            ->getRepository(Role::class)
            ->hasAssignedUsers($role);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                Router::class,
                RolePrivilegeCategoryProvider::class,
                RolePrivilegeCapabilityProvider::class,
                AclRoleHandler::class,
                ConnectionChecker::class,
                WebsocketClient::class,
                ManagerRegistry::class,
            ]
        );
    }
}
