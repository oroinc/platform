<?php

namespace Oro\Bundle\UserBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class UserController extends Controller
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
     */
    public function viewAction(User $user)
    {
        return $this->view($user);
    }

    /**
     * @Route("/profile/view", name="oro_user_profile_view")
     * @Template("OroUserBundle:User:view.html.twig")
     */
    public function viewProfileAction()
    {
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
     * @Route("/apigen/{id}", name="oro_user_apigen", requirements={"id"="\d+"})
     */
    public function apigenAction(User $user)
    {
        $securityFacade = $this->get('oro_security.security_facade');
        if ($securityFacade->getLoggedUserId() !== $user->getId() && !$securityFacade->isGranted('EDIT', $user)) {
            throw $this->createAccessDeniedException();
        }

        $em      = $this->getDoctrine()->getManager();
        $userApi = $this->getUserApi($user);
        $userApi->setApiKey($userApi->generateKey())
            ->setUser($user)
            ->setOrganization($this->getOrganization());

        $em->persist($userApi);
        $em->flush();

        return $this->getRequest()->isXmlHttpRequest()
            ? new JsonResponse($userApi->getApiKey())
            : $this->forward('OroUserBundle:User:view', array('user' => $user));
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
        return array(
            'entity_class' => $this->container->getParameter('oro_user.entity.class')
        );
    }

    /**
     * @param User  $entity
     *
     * @return RedirectResponse|array
     */
    protected function update(User $entity)
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
            // TODO: it is a temporary solution. In a future it is planned to give an user a choose what to do:
            // completely delete an owner and related entities or reassign related entities to another owner before
            'allow_delete' => $this->isUserDeleteAllowed($entity)
        ];
    }

    /**
     * @param User $entity
     * @param bool $isProfileView
     * @return array
     */
    protected function view(User $entity, $isProfileView = false)
    {
        // TODO: it is a temporary solution. In a future it is planned to give an user a choose what to do:
        // completely delete an owner and related entities or reassign related entities to another owner before
        
        return [
            'entity' => $entity,
            'allow_delete' => $this->isUserDeleteAllowed($entity),
            'isProfileView' => $isProfileView
        ];
    }

    /**
     * @return BusinessUnitManager
     */
    protected function getBusinessUnitManager()
    {
        return $this->get('oro_organization.business_unit_manager');
    }

    /**
     * @Route("/widget/info/{id}", name="oro_user_widget_info", requirements={"id"="\d+"})
     * @Template
     */
    public function infoAction(User $user)
    {
        return array(
            'entity'      => $user,
            'userApi'     => $this->getUserApi($user),
            'viewProfile' => (bool)$this->getRequest()->query->get('viewProfile', false)
        );
    }

    /**
     * Returns current UserApi or creates new one
     *
     * @param User $user
     *
     * @return UserApi
     */
    protected function getUserApi(User $user)
    {
        $userManager  = $this->get('oro_user.manager');
        if (!$userApi = $userManager->getApi($user, $this->getOrganization())) {
            $userApi = new UserApi();
        }

        return $userApi;
    }

    /**
     * Returns current organization
     *
     * @return Organization
     */
    protected function getOrganization()
    {
        /** @var UsernamePasswordOrganizationToken $token */
        $token = $this->get('security.context')->getToken();
        return $token->getOrganizationContext();
    }

    /**
     * @param User $entity
     *
     * @return bool
     */
    protected function isUserDeleteAllowed(User $entity)
    {
        $isDeleteAllowed = $entity->getId()
            && $this->getUser()->getId() !== $entity->getId()
            && !$this->get('oro_organization.owner_deletion_manager')->hasAssignments($entity);

        return $isDeleteAllowed;
    }

    /**
     * @TODO Test, please remove before merge
     *
     * @Route(
     *      "/index_search_grid",
     *      name="oro_user_index_search_grid",
     * )
     * @Template
     * @AclAncestor("oro_user_user_view")
     */
    public function indexSearchGridAction()
    {
        return array(
            'entity_class' => $this->container->getParameter('oro_user.entity.class')
        );
    }
}
