<?php

namespace Oro\Bundle\UserBundle\Controller;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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
     * @param Role $entity
     * @return array
     */
    protected function update(Role $entity)
    {
        $aclRoleHandler = $this->get('oro_user.form.handler.acl_role');
        $aclRoleHandler->createForm($entity);

        if ($aclRoleHandler->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.user.controller.role.message.saved')
            );

            return $this->get('oro_ui.router')->redirect($entity);
        }
        
        $categoriesList = $this->get('oro_user.provider.category_provider')->getList();
        $categories = array_values($categoriesList);
        $tabs = array_filter(array_map(function($category) {
            return $category['tab'] ? $category['id'] : null;
        }, $categoriesList));
        // @todo: redevelop it as grid
        $form = $aclRoleHandler->createView();
        $translator = $this->get('translator');
        $permissionManager = $this->get('oro_security.acl.permission_manager');
        /** @var ConfigProvider $configEntityManager */
        $configEntityManager = $this->get('oro_entity_config.provider.entity');
        $form->children['entity']->children;
        $gridData = [];
        foreach ($form->children['entity']->children as $child) {
            $identity = $child->children['identity'];
            $oid = $identity->children['id']->vars['value'];
            $item = [
                'entity' => $translator->trans($identity->children['name']->vars['value']),
                'identity' => $identity->children['id']->vars['value'],
                'group' => ['account_management', 'marketing', 'sales_data', null][count($gridData) % 4],
                'permissions' => []
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

            // all data transformation are taken from form type blocks
            foreach ($child->vars['privileges_config']['permissions'] as $field) {
                foreach ($child->children['permissions']->children as $permission) {
                    if ($permission->vars['value']->getName() === $field) {
                        $accessLevelVars = $permission->children['accessLevel']->vars;
                        $permissionEntity = $permissionManager->getPermissionByName($field);
                        $permissionLabel = $permissionEntity->getLabel() ? $permissionEntity->getLabel() : $field;
                        $permissionDescription = '';
                        if ($permissionEntity->getDescription()) {
                            $permissionDescription = $translator->trans($permissionEntity->getDescription());
                        }
                        $valueText = $accessLevelVars['translation_prefix'] .
                            (empty($accessLevelVars['level_label']) ? 'NONE' : $accessLevelVars['level_label']);
                        $valueText = $translator->trans($valueText, [], $accessLevelVars['translation_domain']);
                        $item['permissions'][] = [
                            'id' => $permissionEntity->getId(),
                            'name' => $permissionEntity->getName(),
                            'label' => $translator->trans($permissionLabel),
                            'description' => $permissionDescription,
                            'full_name' => $accessLevelVars['full_name'],
                            'identity' => $accessLevelVars['identity'],
                            'access_level' => $accessLevelVars['value'],
                            'access_level_label' => $valueText
                        ];
                        break;
                    }
                }
            }

            $gridData[] = $item;
        }

        $capabilitiesData = [];
        foreach ($categories as $category) {
            $capabilitiesData[] = [
                'group' => $category['id'],
                'label' => $category['label'],
                'items' => []
            ];
        }

        $index = 0;
        foreach ($form->children['action']->children as $action_id => $child) {
            $permission = reset($child->children['permissions']->children)->vars['value'];
            $description = $child->vars['value']->getDescription();
            $capabilitiesData[$index++ % count($categories)]['items'][] = [
                'id' => $action_id,
                'identity' => $child->children['identity']->children['id']->vars['value'],
                'label' => $translator->trans($child->children['identity']->children['name']->vars['value']),
                'description' => $description ? $translator->trans($description) : '',
                'name' => $permission->getName(),
                'access_level' => $permission->getAccessLevel(),
                'selected_access_level' => 5,
                'unselected_access_level' => 0
            ];
        }

        $gridDatasource = $this->get('oro_user.datagrid.datasource.repository');
        $gridDatasource->setResults($gridData);
        
        return array(
            'entity' => $entity,
            'form' => $form,
            'tabsOptions' => [
                'data' => array_filter($categories, function ($category) use ($tabs) {
                    return in_array($category['id'], $tabs);
                })
            ],
            'gridData' => $gridData,
            'capabilitySetOptions' => [
                'data' => array_values($capabilitiesData),
                'tabIds' => $tabs
            ],
            'privilegesConfig' => $this->container->getParameter('oro_user.privileges'),
            // TODO: it is a temporary solution. In a future it is planned to give an user a choose what to do:
            // completely delete a role and un-assign it from all users or reassign users to another role before
            'allow_delete' =>
                $entity->getId() &&
                !$this->get('doctrine.orm.entity_manager')
                    ->getRepository('OroUserBundle:Role')
                    ->hasAssignedUsers($entity)
        );
    }
}
