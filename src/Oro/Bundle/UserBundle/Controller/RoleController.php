<?php

namespace Oro\Bundle\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Bundle\UserBundle\Model\PermissionCategory;
use Oro\Bundle\UserBundle\Entity\Role;
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
     * @Acl(
     *      id="oro_user_role_update",
     *      type="entity",
     *      class="OroUserBundle:Role",
     *      permission="EDIT"
     * )
     * @Route("/update/{id}", name="oro_user_role_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template
     */
    public function updateAction(Role $entity)
    {
        return $this->update($entity);
    }

    /**
     * @Route(
     *      "/clone/{id}",
     *      name="oro_user_role_clone",
     *      requirements={"id"="\d+"}
     * )
     * @AclAncestor("oro_user_role_create")
     * @Template("OroUserBundle:Role:update.html.twig")
     *
     * @param Role $entity
     * @return array
     */
    public function cloneAction(Role $entity)
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        $clonedLabel = $translator->trans('oro.user.role.clone.label', array('%name%' => $entity->getLabel()));

        $clonedRole = clone $entity;
        $clonedRole->setLabel($clonedLabel);

        return $this->update($clonedRole);
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
    public function indexAction(Request $request)
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
        $categoryProvider = $this->get('oro_user.provider.role_permission_category_provider');

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
        $tabs = array_map(function ($tab) {
            /** @var PermissionCategory $tab */
            return [
                'id' => $tab->getId(),
                'label' => $this->get('translator')->trans($tab->getLabel())
            ];
        }, $categoryProvider->getTabbedCategories());

        return array(
            'entity' => $role,
            'form' => $form,
            'tabsOptions' => [
                'data' => $tabs
            ],
            'capabilitySetOptions' => [
                'data' => $this->get('oro_user.provider.role_permission_capability_provider')->getCapabilities($role),
                'tabIds' => $categoryProvider->getTabList()
            ],
            'privilegesConfig' => $this->container->getParameter('oro_user.privileges'),
            // TODO: it is a temporary solution. In a future it is planned to give an user a choose what to do:
            // completely delete a role and un-assign it from all users or reassign users to another role before
            'allow_delete' =>
                $role->getId() &&
                !$this->get('doctrine.orm.entity_manager')
                    ->getRepository('OroUserBundle:Role')
                    ->hasAssignedUsers($role)
        );
    }
}
