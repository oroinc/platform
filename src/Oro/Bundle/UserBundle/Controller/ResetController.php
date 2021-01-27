<?php

namespace Oro\Bundle\UserBundle\Controller;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles request and reset password logic
 */
class ResetController extends AbstractController
{
    const SESSION_EMAIL = 'oro_user_reset_email';

    /**
     * Request reset user password
     *
     * @param Request $request
     * @Route("/send-email", name="oro_user_reset_send_email", methods={"POST"})
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sendEmailAction(Request $request)
    {
        if (!$this->isCsrfTokenValid('oro-user-password-reset-request', $request->get('_csrf_token'))) {
            $this->get('session')->getFlashBag()
                ->add('warn', 'The CSRF token is invalid. Please try to resubmit the form.');
            return $this->redirect($this->generateUrl('oro_user_reset_request'));
        }
        $email = $request->request->get('username');
        $inputData = $email;

        $userManager = $this->getUserManager();
        /** @var User $user */
        $user = $userManager->findUserByUsernameOrEmail($email);

        if (null !== $user && $user->isEnabled()) {
            $email = $user->getEmail();

            if ($user->isPasswordRequestNonExpired($this->container->getParameter('oro_user.reset.ttl'))
                && !($request->get('frontend', false) && null === $user->getPasswordRequestedAt())
            ) {
                $securityLogMessage = 'The password for this user has already been requested within the last 24 hours.';
            } else {
                try {
                    $userManager->sendResetPasswordEmail($user);
                } catch (\Exception $e) {
                    $this->get('logger')->error(
                        'Unable to sent the reset password email.',
                        ['email' => $email, 'exception' => $e]
                    );
                    $this->get('session')->getFlashBag()
                        ->add('warn', $this->get('translator')->trans('oro.email.handler.unable_to_send_email'));

                    return $this->redirect($this->generateUrl('oro_user_reset_request'));
                }

                $securityLogMessage = 'Reset password email has been sent';
                $userManager->updateUser($user);
            }

            $this->get('monolog.logger.oro_account_security')->notice(
                $securityLogMessage,
                $this->get('oro_user.provider.user_logging_info_provider')->getUserLoggingInfo($user)
            );
        }

        $this->get('session')->set(static::SESSION_EMAIL, $inputData);

        return $this->redirect($this->generateUrl('oro_user_reset_check_email'));
    }

    /**
     * @Route("/reset-request", name="oro_user_reset_request", methods={"GET"})
     * @Template
     */
    public function requestAction()
    {
        return array();
    }

    /**
     * @param Request $request
     * @param User $user
     * @Route(
     *     "/send-forced-password-reset-email/{id}",
     *     name="oro_user_send_forced_password_reset_email",
     *     requirements={"id"="\d+"}
     * )
     * @AclAncestor("password_management")
     * @Template("OroUserBundle:Reset/dialog:forcePasswordResetConfirmation.html.twig")
     *
     * @return array
     */
    public function sendForcedResetEmailAction(Request $request, User $user)
    {
        $params = [
            'entity' => $user
        ];

        if (!$request->isMethod('POST')) {
            $params['formAction'] = $this->get('router')->generate(
                'oro_user_send_forced_password_reset_email',
                ['id' => $user->getId()]
            );

            return $params;
        }

        $session = $this->get('session');
        $em = $this->get('doctrine.orm.entity_manager');
        $resetPasswordHandler = $this->get('oro_user.handler.reset_password_handler');
        $translator = $this->get('translator');

        $resetPasswordSuccess = $resetPasswordHandler->resetPasswordAndNotify($user);
        $em->flush();

        $flashBag = $session->getFlashBag();

        if ($resetPasswordSuccess) {
            $flashBag->add(
                'success',
                $translator->trans('oro.user.password.force_reset.success.message', ['%email%' => $user->getEmail()])
            );

            if ($this->getUser() && $this->getUser()->getId() === $user->getId()) {
                return $this->redirectToRoute('oro_user_security_login');
            }
            return $params;
        }

        $flashBag->add(
            'error',
            $translator->trans('oro.user.password.force_reset.failure.message', ['%email%' => $user->getEmail()])
        );

        return $params;
    }

    /**
     * @Route(
     *     "/mass-password-reset/",
     *     name="oro_user_mass_password_reset"
     * )
     * @AclAncestor("password_management")
     * @CsrfProtection()
     */
    public function massPasswordResetAction(Request $request)
    {
        $gridName = $request->get('gridName');
        $actionName = $request->get('actionName');

        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->get('oro_datagrid.mass_action.dispatcher');

        $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $request);

        $data = [
            'successful' => $response->isSuccessful(),
            'message' => $response->getMessage()
        ];

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }

    /**
     * Tell the user to check his email provider
     *
     * @Route("/check-email", name="oro_user_reset_check_email", methods={"GET"})
     * @Template
     */
    public function checkEmailAction()
    {
        $session = $this->get('session');
        $email = $session->get(static::SESSION_EMAIL);

        $session->remove(static::SESSION_EMAIL);

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return $this->redirect($this->generateUrl('oro_user_reset_request'));
        }

        return array(
            'email' => $email,
        );
    }

    /**
     * Reset user password
     *
     * @Route("/reset/{token}", name="oro_user_reset_reset", requirements={"token"="\w+"}, methods={"GET", "POST"})
     * @Template
     */
    public function resetAction($token)
    {
        $user = $this->getUserManager()->findUserByConfirmationToken($token);
        $session = $this->get('session');

        if (null === $user) {
            throw $this->createNotFoundException(
                sprintf('The user with "confirmation token" does not exist for value "%s"', $token)
            );
        }

        if (!$user->isPasswordRequestNonExpired($this->container->getParameter('oro_user.reset.ttl'))) {
            $session->getFlashBag()->add(
                'warn',
                'The password for this user has already been requested within the last 24 hours.'
            );

            return $this->redirect($this->generateUrl('oro_user_reset_request'));
        }

        if ($this->get('oro_user.form.handler.reset')->process($user)) {
            // force user logout
            $session->invalidate();
            $this->get('security.token_storage')->setToken(null);

            $session->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.user.security.password_reseted.message')
            );

            return $this->redirect($this->generateUrl('oro_user_security_login'));
        }

        return array(
            'token' => $token,
            'form'  => $this->get('oro_user.form.reset')->createView(),
        );
    }

    /**
     * Sets user password
     * @AclAncestor("password_management")
     * @Route(
     *     "/set-password/{id}",
     *     name="oro_user_reset_set_password",
     *     requirements={"id"="\d+"},
     *     methods={"GET", "POST"}
     * )
     * @Template("OroUserBundle:Reset/dialog:update.html.twig")
     * @param Request $request
     * @param User $entity
     * @return array
     */
    public function setPasswordAction(Request $request, User $entity)
    {
        $entityRoutingHelper = $this->getEntityRoutingHelper();

        $formAction = $entityRoutingHelper->generateUrlByRequest(
            'oro_user_reset_set_password',
            $request,
            ['id' => $entity->getId()]
        );

        $responseData = [
            'entity' => $entity,
            'saved'  => false
        ];

        if ($this->get('oro_user.form.handler.set_password')->process($entity)) {
            $responseData['entity'] = $entity;
            $responseData['saved']  = true;
        }
        $responseData['form']       = $this->get('oro_user.form.type.set_password.form')->createView();
        $responseData['formAction'] = $formAction;

        return $responseData;
    }

    /**
     * @return EntityRoutingHelper
     */
    protected function getEntityRoutingHelper()
    {
        return $this->get('oro_entity.routing_helper');
    }

    /**
     * @return UserManager
     */
    protected function getUserManager()
    {
        return $this->get('oro_user.manager');
    }
}
