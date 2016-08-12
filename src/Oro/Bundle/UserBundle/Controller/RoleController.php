<?php

namespace Oro\Bundle\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCapabilityProvider;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Route("/role")
 */
class RoleController extends Controller
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
            'tabsOptions' => [
                'data' => $this->getTabListOptions()
            ],
            'capabilitySetOptions' => [
                'data' => $this->getRolePrivilegeCapabilityProvider()->getCapabilities($role),
                'tabIds' => $this->getRolePrivilegeCategoryProvider()->getTabList(),
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
        return array(
            'entity_class' => $this->container->getParameter('oro_user.role.entity.class')
        );
    }

    /**
     * @param Role $role
     *
     * @return array
     */
    protected function update(Role $role)
    {
        $categoryProvider = $this->get('oro_user.provider.role_privilege_category_provider');

        /** @var AclRoleHandler $aclRoleHandler */
        $aclRoleHandler = $this->get('oro_user.form.handler.acl_role');
        $aclRoleHandler->createForm($role);

        if ($aclRoleHandler->process($role)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.user.controller.role.message.saved')
            );

            return $this->get('oro_ui.router')->redirect($role);
        }

        $form = $aclRoleHandler->createView();
        $tabs = $categoryProvider->getTabs();

        return [
            'entity' => $role,
            'form' => $form,
            'tabsOptions' => [
                'data' => $tabs
            ],
            'capabilitySetOptions' =>
                $this->get('oro_user.provider.role_privilege_capability_provider')->getCapabilitySetOptions($role),
            'privilegesConfig' => $this->container->getParameter('oro_user.privileges'),
            // TODO: it is a temporary solution. In a future it is planned to give an user a choose what to do:
            // completely delete a role and un-assign it from all users or reassign users to another role before
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

    /**
     * @return array
     */
    protected function getTabListOptions()
    {
        return array_map(
            function (PrivilegeCategory $tab) {
                return [
                    'id' => $tab->getId(),
                    'label' => $this->get('translator')->trans($tab->getLabel())
                ];
            },
            $this->getRolePrivilegeCategoryProvider()->getTabbedCategories()
        );
    }
}
