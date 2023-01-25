<?php

namespace Oro\Bundle\UserBundle\Controller;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Form\Handler\ResetHandler;
use Oro\Bundle\UserBundle\Form\Handler\SetPasswordHandler;
use Oro\Bundle\UserBundle\Handler\ResetPasswordHandler;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProvider;
use Oro\Bundle\UserBundle\Provider\UserLoggingInfoProviderInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

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
            $request->getSession()->getFlashBag()
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

            $tokenTtl = $this->getParameter('oro_user.reset.ttl');
            if ($user->isPasswordRequestNonExpired($tokenTtl)
                && !($request->get('frontend', false) && null === $user->getPasswordRequestedAt())
            ) {
                $securityLogMessage = sprintf(
                    'The password for this user has already been requested within the last %d hours.',
                    $tokenTtl / 3600 //reset password token ttl in hours
                );
            } else {
                try {
                    $userManager->sendResetPasswordEmail($user);
                } catch (\Exception $e) {
                    $this->get(LoggerInterface::class)->error(
                        'Unable to sent the reset password email.',
                        ['email' => $email, 'exception' => $e]
                    );
                    $request->getSession()->getFlashBag()
                        ->add(
                            'warn',
                            $this->get(TranslatorInterface::class)->trans('oro.email.handler.unable_to_send_email')
                        );

                    return $this->redirect($this->generateUrl('oro_user_reset_request'));
                }

                $securityLogMessage = 'Reset password email has been sent';
                $userManager->updateUser($user);
            }

            $this->get(LoggerInterface::class)->notice(
                $securityLogMessage,
                $this->get(UserLoggingInfoProvider::class)->getUserLoggingInfo($user)
            );
        }

        $request->getSession()->set(static::SESSION_EMAIL, $inputData);

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
     * @Template("@OroUser/Reset/dialog/forcePasswordResetConfirmation.html.twig")
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

        $resetPasswordHandler = $this->get(ResetPasswordHandler::class);
        $translator = $this->get(TranslatorInterface::class);

        $resetPasswordSuccess = $resetPasswordHandler->resetPasswordAndNotify($user);
        $em = $this->get('doctrine')->getManagerForClass(User::class);
        $em->flush();

        $flashBag = $request->getSession()->getFlashBag();

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
        $massActionDispatcher = $this->get(MassActionDispatcher::class);

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
    public function checkEmailAction(Request $request)
    {
        $session = $request->getSession();
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
    public function resetAction(string $token, Request $request)
    {
        $user = $this->getUserManager()->findUserByConfirmationToken($token);
        $session = $request->getSession();

        if (null === $user) {
            throw $this->createNotFoundException(
                sprintf('The user with "confirmation token" does not exist for value "%s"', $token)
            );
        }

        if (!$user->isPasswordRequestNonExpired($this->getParameter('oro_user.reset.ttl'))) {
            $session->getFlashBag()->add(
                'warn',
                'oro.user.password.reset.ttl_expired.message'
            );

            return $this->redirect($this->generateUrl('oro_user_reset_request'));
        }

        if ($this->get(ResetHandler::class)->process($user)) {
            // force user logout
            $session->invalidate();
            $this->get('security.token_storage')->setToken(null);

            $session->getFlashBag()->add(
                'success',
                $this->get(TranslatorInterface::class)->trans('oro.user.security.password_reseted.message')
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
     * @Template("@OroUser/Reset/dialog/update.html.twig")
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

        if ($this->get(SetPasswordHandler::class)->process($entity)) {
            $responseData['entity'] = $entity;
            $responseData['saved']  = true;
        }
        $responseData['form']       = $this->get('oro_user.form.type.set_password.form')->createView();
        $responseData['formAction'] = $formAction;

        return $responseData;
    }

    protected function getEntityRoutingHelper(): EntityRoutingHelper
    {
        return $this->get(EntityRoutingHelper::class);
    }

    protected function getUserManager(): UserManager
    {
        return $this->get(UserManager::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                LoggerInterface::class,
                TranslatorInterface::class,
                UserManager::class,
                SetPasswordHandler::class,
                ResetHandler::class,
                MassActionDispatcher::class,
                UserLoggingInfoProvider::class => UserLoggingInfoProviderInterface::class,
                EntityRoutingHelper::class,
                LoggerInterface::class,
                ResetPasswordHandler::class,
                'oro_user.form.reset' => Form::class,
                'oro_user.form.type.set_password.form' => Form::class,
            ]
        );
    }
}
