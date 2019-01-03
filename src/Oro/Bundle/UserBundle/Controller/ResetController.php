<?php

namespace Oro\Bundle\UserBundle\Controller;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Util\ObfuscatedEmailTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles request and reset password logic
 */
class ResetController extends Controller
{
    use ObfuscatedEmailTrait;

    const SESSION_EMAIL = 'oro_user_reset_email';

    /**
     * Request reset user password
     *
     * @param Request $request
     * @Route("/send-email", name="oro_user_reset_send_email")
     * @Method({"POST"})
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sendEmailAction(Request $request)
    {
        $email = $request->request->get('username');
        $frontend = $request->get('frontend', false);
        /** @var User $user */
        $user = $this->get('oro_user.manager')->findUserByUsernameOrEmail($email);

        if (null !== $user && $user->isEnabled()) {
            $email = $user->getEmail();
            if ($user->isPasswordRequestNonExpired($this->container->getParameter('oro_user.reset.ttl'))
                && !($frontend && null === $user->getPasswordRequestedAt())
            ) {
                $this->get('session')->getFlashBag()
                    ->add('warn', 'oro.user.password.reset.ttl_already_requested.message');

                return $this->redirect($this->generateUrl('oro_user_reset_request'));
            }

            $user->setConfirmationToken($user->generateToken());
            try {
                $this->get('oro_user.mailer.processor')->sendResetPasswordEmail($user);
            } catch (\Exception $e) {
                $this->get('logger')->error(
                    'Unable to sent the reset password email.',
                    ['email' => $email, 'exception' => $e]
                );
                $this->get('session')->getFlashBag()
                    ->add('warn', $this->get('translator')->trans('oro.email.handler.unable_to_send_email'));

                return $this->redirect($this->generateUrl('oro_user_reset_request'));
            }
            $user->setPasswordRequestedAt(new \DateTime('now', new \DateTimeZone('UTC')));
            $this->get('oro_user.manager')->updateUser($user);
        }

        $this->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($email));

        return $this->redirect($this->generateUrl('oro_user_reset_check_email'));
    }

    /**
     * @Route("/reset-request", name="oro_user_reset_request")
     * @Method({"GET"})
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

        $session->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user->getEmail()));

        $resetPasswordSuccess = $resetPasswordHandler->resetPasswordAndNotify($user);
        $em->flush();

        $flashBag = $session->getFlashBag();

        if ($resetPasswordSuccess) {
            $flashBag->add(
                'success',
                $translator->trans('oro.user.password.force_reset.success.message', ['%email%' => $user->getEmail()])
            );

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
     * @Route("/check-email", name="oro_user_reset_check_email")
     * @Method({"GET"})
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
     * @Route("/reset/{token}", name="oro_user_reset_reset", requirements={"token"="\w+"})
     * @Method({"GET", "POST"})
     * @Template
     */
    public function resetAction($token)
    {
        $user = $this->get('oro_user.manager')->findUserByConfirmationToken($token);
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
     * @Method({"GET", "POST"})
     * @Route("/set-password/{id}", name="oro_user_reset_set_password", requirements={"id"="\d+"})
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
}
