<?php

namespace Oro\Bundle\UserBundle\Controller;

use FOS\RestBundle\Util\Codes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserApi;

use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\HttpFoundation\Response;

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
        return $this->view($this->getUser(), 'oro_user_profile_update');
    }

    /**
     * @Route("/profile/edit", name="oro_user_profile_update")
     * @Template("OroUserBundle:User/Profile:update.html.twig")
     */
    public function updateProfileAction()
    {
        $entity = $this->getUser();
        return $this->update(
            $entity,
            'oro_user_profile_update',
            array('route' => 'oro_user_profile_view'),
            'oro_user_profile_view'
        );
    }

    /**
     * @Route("/apigen/{id}", name="oro_user_apigen", requirements={"id"="\d+"})
     */
    public function apigenAction(User $user)
    {
        $securityFacade = $this->get('oro_security.security_facade');
        $currentAuthenticatedUser = $this->getUser();
        if ($currentAuthenticatedUser !== $user && !$securityFacade->isGranted('EDIT', $user)) {
            return $this->getRequest()->isXmlHttpRequest()
                ? new JsonResponse('', Response::HTTP_FORBIDDEN)
                : new Response('', Response::HTTP_FORBIDDEN);
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
     * @param User   $entity
     * @param string $updateRoute
     * @param array  $viewRoute
     * @param string $cancelRoute
     * @return mixed
     */
    protected function update(User $entity, $updateRoute = '', $viewRoute = array(), $cancelRoute = 'oro_user_index')
    {
        if ($this->get('oro_user.form.handler.user')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.user.controller.user.message.saved')
            );

            if (count($viewRoute)) {
                $closeButtonRoute = $viewRoute;
            } else {
                $closeButtonRoute = array(
                    'route' => 'oro_user_view',
                    'parameters' => array('id' => $entity->getId())
                );
            }
            return $this->get('oro_ui.router')->redirectAfterSave(
                ['route' => 'oro_user_update', 'parameters' => ['id' => $entity->getId()]],
                $closeButtonRoute,
                $entity
            );
        }

        return array(
            'entity'        => $entity,
            'form'          => $this->get('oro_user.form.user')->createView(),
            'editRoute'     => $updateRoute,
            'cancelRoute'   => $cancelRoute,
            // TODO: it is a temporary solution. In a future it is planned to give an user a choose what to do:
            // completely delete an owner and related entities or reassign related entities to another owner before
            'allow_delete' =>
                $this->getUser()->getId() !== $entity->getId() &&
                $entity->getId() &&
                !$this->get('oro_organization.owner_deletion_manager')->hasAssignments($entity)
        );
    }

    /**
     * @param User $entity
     * @param string $editRoute
     * @return array
     */
    protected function view(User $entity, $editRoute = '')
    {
        $output = array(
            'entity' => $entity,
            // TODO: it is a temporary solution. In a future it is planned to give an user a choose what to do:
            // completely delete an owner and related entities or reassign related entities to another owner before
            'allow_delete' =>
                $this->getUser()->getId() !== $entity->getId() &&
                !$this->get('oro_organization.owner_deletion_manager')->hasAssignments($entity)
        );

        if ($editRoute) {
            $output = array_merge($output, array('editRoute' => $editRoute));
        }
        return $output;
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
}
