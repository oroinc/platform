<?php

namespace Oro\Bundle\UserBundle\Controller;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Form\Type\UserApiKeyGenType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * This controller covers CRUD functionality for User entity.
 * Also includes user profile management functionality.
 */
class UserController extends AbstractController
{
    /**
     * @Route("/view/{id}", name="oro_user_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_user_user_view",
     *      type="entity",
     *      class="OroUserBundle:User",
     *      permission="VIEW"
     * )
     *
     * @param User $user
     * @return array
     */
    public function viewAction(User $user)
    {
        return $this->view(
            $user,
            $this->get('oro_security.token_accessor')->getUserId() === $user->getId()
        );
    }

    /**
     * @Route("/profile/view", name="oro_user_profile_view")
     * @Template("OroUserBundle:User:view.html.twig")
     */
    public function viewProfileAction()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->view($this->getUser(), true);
    }

    /**
     * @Route("/profile/edit", name="oro_user_profile_update")
     * @Template("OroUserBundle:User/Profile:update.html.twig")
     * @AclAncestor("update_own_profile")
     */
    public function updateProfileAction()
    {
        return $this->update($this->getUser());
    }

    /**
     * @Route("/apigen/{id}", name="oro_user_apigen", requirements={"id"="\d+"}, methods={"GET", "POST"})
     *
     * @param User $user
     * @return JsonResponse|Response
     */
    public function apigenAction(User $user)
    {
        if (!$this->isUserApiGenAllowed($user)) {
            throw $this->createAccessDeniedException();
        }
        $userApi = $this->getUserApi($user);
        $form = $this->createForm(UserApiKeyGenType::class, $userApi);

        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->getMethod() === 'POST') {
            $userApi->setApiKey($userApi->generateKey());
            $form->setData($userApi);
            $form->handleRequest($request);

            $responseData = ['data' => [], 'status' => 'success'];
            $status = Response::HTTP_OK;
            if ($form->isSubmitted() && $form->isValid()) {
                $this->saveUserApi($user, $userApi);
                $responseData['data'] = ['apiKey' => $userApi->getApiKey()];
            } else {
                $status = Response::HTTP_BAD_REQUEST;
                $responseData['status'] = 'error';
                $responseData['errors'] = $form->getErrors();
            }

            return new JsonResponse($responseData, $status);
        }

        return $this->render(
            'OroUserBundle:User/widget:apiKeyGen.html.twig',
            ['form' => $form->createView(), 'user' => $user]
        );
    }

    /**
     * Create user form
     *
     * @Route("/create", name="oro_user_create")
     * @Template("OroUserBundle:User:update.html.twig")
     * @Acl(
     *      id="oro_user_user_create",
     *      type="entity",
     *      class="OroUserBundle:User",
     *      permission="CREATE"
     * )
     */
    public function createAction()
    {
        $user = $this->get('oro_user.manager')->createUser();

        return $this->update($user);
    }

    /**
     * Edit user form
     *
     * @Route("/update/{id}", name="oro_user_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template("OroUserBundle:User:update.html.twig")
     * @Acl(
     *      id="oro_user_user_update",
     *      type="entity",
     *      class="OroUserBundle:User",
     *      permission="EDIT"
     * )
     *
     * @param User $entity
     * @return array|RedirectResponse
     */
    public function updateAction(User $entity)
    {
        return $this->update($entity);
    }

    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_user_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Template
     * @AclAncestor("oro_user_user_view")
     */
    public function indexAction()
    {
        return [
            'entity_class' => User::class
        ];
    }

    /**
     * @param User  $entity
     *
     * @return RedirectResponse|array
     */
    private function update(User $entity)
    {
        if ($this->get('oro_user.form.handler.user')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.user.controller.user.message.saved')
            );

            return $this->get('oro_ui.router')->redirect($entity);
        }

        return [
            'entity'       => $entity,
            'form'         => $this->get('oro_user.form.user')->createView(),
            'allow_delete' => $entity->getId() && $this->isDeleteGranted($entity)
        ];
    }

    /**
     * @param User $entity
     * @param bool $isProfileView
     * @return array
     */
    private function view(User $entity, $isProfileView = false)
    {
        return [
            'entity'        => $entity,
            'allow_delete'  => $this->isDeleteGranted($entity),
            'isProfileView' => $isProfileView
        ];
    }

    /**
     * @Route("/widget/info/{id}", name="oro_user_widget_info", requirements={"id"="\d+"})
     * @Template
     * @param Request $request
     * @param User $user
     * @return array
     */
    public function infoAction(Request $request, User $user)
    {
        $isViewProfile = (bool)$request->query->get('viewProfile', false);

        if (!(($isViewProfile && $this->getUser()->getId() === $user->getId())
            || $this->isGranted('oro_user_user_view', $user))
        ) {
            throw new AccessDeniedException();
        }

        return [
            'entity'      => $user,
            'userApi'     => $this->getUserApi($user),
            'viewProfile' => $isViewProfile
        ];
    }

    /**
     * Returns current UserApi or creates new one
     *
     * @param User $user
     *
     * @return UserApi
     */
    private function getUserApi(User $user)
    {
        $userManager  = $this->get('oro_user.manager');
        if (!$userApi = $userManager->getApi($user, $this->getOrganization())) {
            $userApi = new UserApi();
            $userApi->setUser($user);
        }

        return $userApi;
    }

    /**
     * Returns current organization
     *
     * @return Organization
     */
    private function getOrganization()
    {
        /** @var UsernamePasswordOrganizationToken $token */
        $token = $this->get('security.token_storage')->getToken();

        return $token->getOrganization();
    }

    private function isDeleteGranted(User $entity): bool
    {
        return $this->get('oro_entity.delete_handler_registry')
            ->getHandler(User::class)
            ->isDeleteGranted($entity);
    }

    /**
     * @param User $entity
     *
     * @return bool
     */
    private function isUserApiGenAllowed(User $entity)
    {
        return $this->get('oro_security.token_accessor')->getUserId() === $entity->getId()
               || $this->isGranted('MANAGE_API_KEY', $entity);
    }

    private function saveUserApi(User $user, UserApi $userApi)
    {
        $em = $this->getDoctrine()->getManagerForClass(User::class);

        $userApi
            ->setUser($user)
            ->setOrganization($this->getOrganization());

        $em->persist($userApi);
        $em->flush();
    }
}
