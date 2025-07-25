<?php

namespace Oro\Bundle\UserBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Form\Handler\ResetHandler;
use Oro\Bundle\UserBundle\Form\Handler\SetPasswordHandler;
use Oro\Bundle\UserBundle\Form\Handler\UserPasswordResetHandler;
use Oro\Bundle\UserBundle\Form\Type\UserPasswordResetRequestType;
use Oro\Bundle\UserBundle\Handler\ResetPasswordHandler;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles request and reset password logic
 */
class ResetController extends AbstractController
{
    const SESSION_EMAIL = 'oro_user_reset_email';

    #[Route(
        path: '/reset-request',
        name: 'oro_user_reset_request',
        methods: ['GET', 'POST'],
        defaults: [
            '_target_route' => 'oro_user_reset_check_email'
        ]
    )]
    #[Template]
    public function requestAction(Request $request)
    {
        $form = $this->createForm(UserPasswordResetRequestType::class);
        $handler = $this->container->get(UserPasswordResetHandler::class);

        $email = $handler->process($form, $request);
        if ($email) {
            $request->getSession()->set(static::SESSION_EMAIL, $email);

            return $this->redirect(
                $this->generateUrl($request->attributes->get('_target_route', 'oro_user_reset_check_email'))
            );
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @param Request $request
     * @param User $user
     * @return array
     */
    #[Route(
        path: '/send-forced-password-reset-email/{id}',
        name: 'oro_user_send_forced_password_reset_email',
        requirements: ['id' => '\d+']
    )]
    #[Template('@OroUser/Reset/dialog/forcePasswordResetConfirmation.html.twig')]
    #[AclAncestor('password_management')]
    public function sendForcedResetEmailAction(Request $request, User $user)
    {
        $params = [
            'entity' => $user
        ];

        if (!$request->isMethod('POST')) {
            $params['formAction'] = $this->container->get('router')->generate(
                'oro_user_send_forced_password_reset_email',
                ['id' => $user->getId()]
            );

            return $params;
        }

        $resetPasswordHandler = $this->container->get(ResetPasswordHandler::class);
        $translator = $this->container->get(TranslatorInterface::class);

        $resetPasswordSuccess = $resetPasswordHandler->resetPasswordAndNotify($user);
        $em = $this->container->get(ManagerRegistry::class)->getManagerForClass(User::class);
        $em->flush();

        $flashBag = $request->getSession()->getFlashBag();

        if ($resetPasswordSuccess) {
            $flashBag->add(
                'success',
                $translator->trans('oro.user.password.force_reset.success.message', ['%email%' => $user->getEmail()])
            );

            if ($this->getUser() instanceof AbstractUser && $this->getUser()->getId() === $user->getId()) {
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

    #[Route(path: '/mass-password-reset/', name: 'oro_user_mass_password_reset')]
    #[AclAncestor('password_management')]
    #[CsrfProtection()]
    public function massPasswordResetAction(Request $request)
    {
        $gridName = $request->get('gridName');
        $actionName = $request->get('actionName');

        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->container->get(MassActionDispatcher::class);

        $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $request);

        $data = [
            'successful' => $response->isSuccessful(),
            'message' => $response->getMessage()
        ];

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }

    /**
     * Tell the user to check his email provider
     */
    #[Route(path: '/check-email', name: 'oro_user_reset_check_email', methods: ['GET'])]
    #[Template]
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
     */
    #[Route(
        path: '/reset/{token}',
        name: 'oro_user_reset_reset',
        requirements: ['token' => '\w+'],
        methods: ['GET', 'POST']
    )]
    #[Template]
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

        if ($this->container->get(ResetHandler::class)->process($user)) {
            // force user logout
            $session->invalidate();
            $this->container->get('security.token_storage')->setToken(null);

            $session->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('oro.user.security.password_reseted.message')
            );

            return $this->redirect($this->generateUrl('oro_user_security_login'));
        }

        return array(
            'token' => $token,
            'form' => $this->container->get('oro_user.form.reset')->createView(),
        );
    }

    /**
     * Sets user password
     * @param Request $request
     * @param User $entity
     * @return array
     */
    #[Route(
        path: '/set-password/{id}',
        name: 'oro_user_reset_set_password',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[Template('@OroUser/Reset/dialog/update.html.twig')]
    #[AclAncestor('password_management')]
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
            'saved' => false
        ];

        if ($this->container->get(SetPasswordHandler::class)->process($entity)) {
            $responseData['entity'] = $entity;
            $responseData['saved'] = true;
        }
        $responseData['form'] = $this->container->get('oro_user.form.type.set_password.form')->createView();
        $responseData['formAction'] = $formAction;

        return $responseData;
    }

    protected function getEntityRoutingHelper(): EntityRoutingHelper
    {
        return $this->container->get(EntityRoutingHelper::class);
    }

    protected function getUserManager(): UserManager
    {
        return $this->container->get(UserManager::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ManagerRegistry::class,
                LoggerInterface::class,
                TranslatorInterface::class,
                UserManager::class,
                SetPasswordHandler::class,
                ResetHandler::class,
                MassActionDispatcher::class,
                EntityRoutingHelper::class,
                LoggerInterface::class,
                ResetPasswordHandler::class,
                UserPasswordResetHandler::class,
                'oro_user.form.reset' => Form::class,
                'oro_user.form.type.set_password.form' => Form::class,
            ]
        );
    }
}
