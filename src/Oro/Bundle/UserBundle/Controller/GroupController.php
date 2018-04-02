<?php

namespace Oro\Bundle\UserBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\UserBundle\Entity\Group;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/group")
 */
class GroupController extends Controller
{
    /**
     * Create group form
     *
     * @Route("/create", name="oro_user_group_create")
     * @Template("OroUserBundle:Group:update.html.twig")
     * @Acl(
     *      id="oro_user_group_create",
     *      type="entity",
     *      class="OroUserBundle:Group",
     *      permission="CREATE"
     * )
     * @param Request $request
     * @return array
     */
    public function createAction(Request $request)
    {
        return $this->update($request, new Group());
    }

    /**
     * Edit group form
     *
     * @Route("/update/{id}", name="oro_user_group_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template
     * @Acl(
     *      id="oro_user_group_update",
     *      type="entity",
     *      class="OroUserBundle:Group",
     *      permission="EDIT"
     * )
     * @param Request $request
     * @return array
     */
    public function updateAction(Request $request, Group $entity)
    {
        return $this->update($request, $entity);
    }

    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_user_group_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Acl(
     *      id="oro_user_group_view",
     *      type="entity",
     *      class="OroUserBundle:Group",
     *      permission="VIEW"
     * )
     * @Template
     */
    public function indexAction(Request $request)
    {
        return array(
            'entity_class' => $this->container->getParameter('oro_user.group.entity.class')
        );
    }

    /**
     * @param Request $request
     * @param Group $entity
     * @return array
     */
    protected function update(Request $request, Group $entity)
    {
        if ($this->get('oro_user.form.handler.group')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.user.controller.group.message.saved')
            );

            if (!$request->get('_widgetContainer')) {
                return $this->get('oro_ui.router')->redirect($entity);
            }
        }

        return array(
            'entity'   => $entity,
            'form'     => $this->get('oro_user.form.group')->createView(),
        );
    }
}
