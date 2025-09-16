<?php

namespace Oro\Bundle\UserBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerRegistry;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UIBundle\Route\Router;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Form\Handler\UserHandler;
use Oro\Bundle\UserBundle\Form\Type\UserType;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This controller covers CRUD functionality for User entity.
 * Also includes user profile management functionality.
 */
class UserController extends AbstractController
{
    #[Route(path: '/view/{id}', name: 'oro_user_view', requirements: ['id' => '\d+'])]
    #[Template('@OroUser/User/view.html.twig')]
    #[Acl(id: 'oro_user_user_view', type: 'entity', class: User::class, permission: 'VIEW')]
    public function viewAction(User $user): array
    {
        return $this->view($user, $user === $this->getUser());
    }

    #[Route(path: '/profile/view', name: 'oro_user_profile_view')]
    #[Template('@OroUser/User/view.html.twig')]
    public function viewProfileAction(): array
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->view($this->getUser(), true);
    }

    #[Route(path: '/profile/edit', name: 'oro_user_profile_update')]
    #[Template('@OroUser/User/Profile/update.html.twig')]
    #[AclAncestor('update_own_profile')]
    public function updateProfileAction(Request $request): array|RedirectResponse
    {
        return $this->update($this->getUser(), $request);
    }

    #[Route(path: '/create', name: 'oro_user_create')]
    #[Template('@OroUser/User/update.html.twig')]
    #[Acl(id: 'oro_user_user_create', type: 'entity', class: User::class, permission: 'CREATE')]
    public function createAction(Request $request): array|RedirectResponse
    {
        $user = $this->container->get(UserManager::class)->createUser();

        return $this->update($user, $request);
    }

    #[Route(path: '/update/{id}', name: 'oro_user_update', requirements: ['id' => '\d+'], defaults: ['id' => 0])]
    #[Template('@OroUser/User/update.html.twig')]
    #[Acl(id: 'oro_user_user_update', type: 'entity', class: User::class, permission: 'EDIT')]
    public function updateAction(User $entity, Request $request): array|RedirectResponse
    {
        return $this->update($entity, $request);
    }

    #[Route(
        path: '/{_format}',
        name: 'oro_user_index',
        requirements: ['_format' => 'html|json'],
        defaults: ['_format' => 'html']
    )]
    #[Template('@OroUser/User/index.html.twig')]
    #[AclAncestor('oro_user_user_view')]
    public function indexAction(): array
    {
        return ['entity_class' => User::class];
    }

    private function update(User $entity, Request $request): RedirectResponse|array
    {
        if ($this->container->get(UserHandler::class)->process($entity)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('oro.user.controller.user.message.saved')
            );

            return $this->container->get(Router::class)->redirect($entity);
        }

        return [
            'entity'       => $entity,
            'form'         => $this->container->get(UserType::class)->createView(),
            'allow_delete' => $entity->getId() && $this->isDeleteGranted($entity)
        ];
    }

    private function view(User $entity, bool $isProfileView = false): array
    {
        return [
            'entity'        => $entity,
            'allow_delete'  => $this->isDeleteGranted($entity),
            'isProfileView' => $isProfileView
        ];
    }

    #[Route(path: '/widget/info/{id}', name: 'oro_user_widget_info', requirements: ['id' => '\d+'])]
    #[Template('@OroUser/User/widget/info.html.twig')]
    public function infoAction(Request $request, User $user): array
    {
        $isViewProfile = (bool)$request->query->get('viewProfile', false);

        if (!(($isViewProfile && $this->getUser()->getId() === $user->getId())
            || $this->isGranted('oro_user_user_view', $user))
        ) {
            throw new AccessDeniedException();
        }

        return [
            'entity'      => $user,
            'viewProfile' => $isViewProfile
        ];
    }

    #[Route(path: '/login-attempts', name: 'oro_user_login_attempts')]
    #[Template('@OroUser/User/loginAttempts.html.twig')]
    #[AclAncestor('oro_view_user_login_attempt')]
    public function loginAttemptsAction(): array
    {
        return [];
    }

    private function isDeleteGranted(User $entity): bool
    {
        return $this->container->get(EntityDeleteHandlerRegistry::class)
            ->getHandler(User::class)
            ->isDeleteGranted($entity);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                Router::class,
                TokenAccessorInterface::class,
                UserManager::class,
                EntityDeleteHandlerRegistry::class,
                UserType::class,
                UserHandler::class,
                TokenStorageInterface::class,
                'doctrine' => ManagerRegistry::class
            ]
        );
    }
}
