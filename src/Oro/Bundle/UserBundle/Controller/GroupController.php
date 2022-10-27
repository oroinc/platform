<?php

namespace Oro\Bundle\UserBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\UIBundle\Route\Router;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Form\Handler\GroupHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD for user groups.
 *
 * @Route("/group")
 */
class GroupController extends AbstractController
{
    /**
     * Create group form
     *
     * @Route("/create", name="oro_user_group_create")
     * @Template("@OroUser/Group/update.html.twig")
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
        return [
            'entity_class' => Group::class
        ];
    }

    /**
     * @param Request $request
     * @param Group $entity
     * @return array
     */
    protected function update(Request $request, Group $entity)
    {
        if ($this->get(GroupHandler::class)->process($entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->get(TranslatorInterface::class)->trans('oro.user.controller.group.message.saved')
            );

            if (!$request->get('_widgetContainer')) {
                return $this->get(Router::class)->redirect($entity);
            }
        }

        return [
            'entity'   => $entity,
            'form'     => $this->get('oro_user.form.group')->createView(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                GroupHandler::class,
                Router::class,
                'oro_user.form.group' => Form::class,
            ]
        );
    }
}
