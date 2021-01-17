<?php

namespace Oro\Bundle\UserBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCapabilityProvider;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Acl\Util\ClassUtils;

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
     *      class="OroUserBundle:Role",
     *      permission="CREATE"
     * )
     * @Route("/create", name="oro_user_role_create")
     * @Template("OroUserBundle:Role:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new Role());
    }

    /**
     * @Route("/view/{id}", name="oro_user_role_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_user_role_view",
     *      type="entity",
     *      class="OroUserBundle:Role",
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
            'allow_delete' =>
                $role->getId() &&
                !$this->get('doctrine.orm.entity_manager')
                    ->getRepository('OroUserBundle:Role')
                    ->hasAssignedUsers($role)
        ];
    }

    /**
     * @Acl(
     *      id="oro_user_role_update",
     *      type="entity",
     *      class="OroUserBundle:Role",
     *      permission="EDIT"
     * )
     * @Route("/update/{id}", name="oro_user_role_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template
     *
     * @param Role $entity
     *
     * @return array
     */
    public function updateAction(Role $entity)
    {
        return $this->update($entity);
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
     *      class="OroUserBundle:Role",
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
     *
     * @return array
     */
    protected function update(Role $role)
    {
        /** @var AclRoleHandler $aclRoleHandler */
        $aclRoleHandler = $this->get('oro_user.form.handler.acl_role');
        $aclRoleHandler->createForm($role);

        if ($aclRoleHandler->process($role)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.user.controller.role.message.saved')
            );

            if ($this->get('oro_sync.client.connection_checker')->checkConnection()) {
                $publisher = $this->get('oro_sync.websocket_client');
                $publisher->publish('oro/outdated_user_page', ['role' => $role->getRole()]);
            }

            return $this->get('oro_ui.router')->redirect($role);
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
            'privilegesConfig' => $this->container->getParameter('oro_user.privileges'),
            'allow_delete' =>
                $role->getId() &&
                !$this->get('doctrine.orm.entity_manager')
                    ->getRepository('OroUserBundle:Role')
                    ->hasAssignedUsers($role)
        ];
    }

    /**
     * @return RolePrivilegeCategoryProvider
     */
    protected function getRolePrivilegeCategoryProvider()
    {
        return $this->get('oro_user.provider.role_privilege_category_provider');
    }

    /**
     * @return RolePrivilegeCapabilityProvider
     */
    protected function getRolePrivilegeCapabilityProvider()
    {
        return $this->get('oro_user.provider.role_privilege_capability_provider');
    }
}
