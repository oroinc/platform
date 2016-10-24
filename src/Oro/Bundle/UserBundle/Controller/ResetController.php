<?php

namespace Oro\Bundle\UserBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\UserBundle\Entity\User;

class ResetController extends Controller
{
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
        $username = $request->request->get('username');
        $frontend = $request->get('frontend', false);
        $user = $this->get('oro_user.manager')->findUserByUsernameOrEmail($username);

        if (null === $user || !$user->isEnabled()) {
            return $this->render('OroUserBundle:Reset:request.html.twig', array('invalid_username' => $username));
        }

        if ($user->isPasswordRequestNonExpired($this->container->getParameter('oro_user.reset.ttl'))) {
            if (!($frontend && null === $user->getPasswordRequestedAt())) {
                $this->get('session')->getFlashBag()->add(
                    'warn',
                    'oro.user.password.reset.ttl_already_requested.message'
                );

                return $this->redirect($this->generateUrl('oro_user_reset_request'));
            }
        }

        if (null === $user->getConfirmationToken()) {
            $user->setConfirmationToken($user->generateToken());
        }

        $this->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user));
        try {
            $this->get('oro_user.mailer.processor')->sendResetPasswordEmail($user);
        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add(
                'warn',
                $this->get('translator')->trans('oro.email.handler.unable_to_send_email')
            );

            return $this->redirect($this->generateUrl('oro_user_reset_request'));
        }
        $user->setPasswordRequestedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->get('oro_user.manager')->updateUser($user);

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
     * @Route(
     *     "/send-forced-password-reset-email/{id}",
     *     name="oro_user_send_forced_password_reset_email",
     *     requirements={"id"="\d+"}
     * )
     * @AclAncestor("password_management")
     * @Template("OroUserBundle:Reset/widget:forcePasswordResetConfirmation.html.twig")
     */
    public function sendForcedResetEmailAction(Request $request, User $user)
    {
        $params = [
            'entity' => $user
        ];
        if ($request->isMethod('POST')) {
            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken($user->generateToken());
            }

            $this->get('session')->set(static::SESSION_EMAIL, $this->getObfuscatedEmail($user));
            try {
                $this->get('oro_user.mailer.processor')->sendForcedResetPasswordAsAdminEmail($user);
            } catch (\Exception $e) {
                $this->get('logger')->addCritical($e->getMessage(), ['exception' => $e]);

                $params['processed'] = false;
                $params['error'] = $this->get('translator')->trans('oro.email.handler.unable_to_send_email');

                return $params;
            }
            $user->setLoginDisabled(true);
            $this->get('oro_user.manager')->updateUser($user);
            $params['processed'] = true;
        } else {
            $params['formAction'] = $this->get('router')->generate(
                'oro_user_send_forced_password_reset_email',
                ['id' => $user->getId()]
            );
        }

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
            $this->get('security.context')->setToken(null);

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
     *
     * @AclAncestor("password_management")
     * @Method({"GET", "POST"})
     * @Route("/set-password/{id}", name="oro_user_reset_set_password", requirements={"id"="\d+"})
     * @Template("OroUserBundle:Reset:update.html.twig")
     */
    public function setPasswordAction(User $entity)
    {
        $entityRoutingHelper = $this->getEntityRoutingHelper();

        $formAction = $entityRoutingHelper->generateUrlByRequest(
            'oro_user_reset_set_password',
            $this->getRequest(),
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
     * Get the truncated email displayed when requesting the resetting.
     * The default implementation only keeps the part following @ in the address.
     *
     * @param User $user
     *
     * @return string
     */
    protected function getObfuscatedEmail(User $user)
    {
        $email = $user->getEmail();

        if (false !== $pos = strpos($email, '@')) {
            $email = '...' . substr($email, $pos);
        }

        return $email;
    }

    /**
     * @return EntityRoutingHelper
     */
    protected function getEntityRoutingHelper()
    {
        return $this->get('oro_entity.routing_helper');
    }
}
