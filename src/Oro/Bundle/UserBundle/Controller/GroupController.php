<?php

namespace Oro\Bundle\UserBundle\Controller;

use Oro\Bundle\SecurityBundle\Attribute\Acl;
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
 */
#[Route(path: '/group')]
class GroupController extends AbstractController
{
    /**
     * Create group form
     *
     * @param Request $request
     * @return array
     */
    #[Route(path: '/create', name: 'oro_user_group_create')]
    #[Template('@OroUser/Group/update.html.twig')]
    #[Acl(id: 'oro_user_group_create', type: 'entity', class: Group::class, permission: 'CREATE')]
    public function createAction(Request $request)
    {
        return $this->update($request, new Group());
    }

    /**
     * Edit group form
     *
     * @param Request $request
     * @return array
     */
    #[Route(path: '/update/{id}', name: 'oro_user_group_update', requirements: ['id' => '\d+'], defaults: ['id' => 0])]
    #[Template]
    #[Acl(id: 'oro_user_group_update', type: 'entity', class: Group::class, permission: 'EDIT')]
    public function updateAction(Request $request, Group $entity)
    {
        return $this->update($request, $entity);
    }

    #[Route(
        path: '/{_format}',
        name: 'oro_user_group_index',
        requirements: ['_format' => 'html|json'],
        defaults: ['_format' => 'html']
    )]
    #[Template]
    #[Acl(id: 'oro_user_group_view', type: 'entity', class: Group::class, permission: 'VIEW')]
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
        if ($this->container->get(GroupHandler::class)->process($entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('oro.user.controller.group.message.saved')
            );

            if (!$request->get('_widgetContainer')) {
                return $this->container->get(Router::class)->redirect($entity);
            }
        }

        return [
            'entity'   => $entity,
            'form'     => $this->container->get('oro_user.form.group')->createView(),
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
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
