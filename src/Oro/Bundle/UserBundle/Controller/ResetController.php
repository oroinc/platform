<?php
namespace Oro\Bundle\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class ResetController extends Controller
{
    const SESSION_EMAIL = 'oro_user_reset_email';

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
     * Request reset user password
     *
     * @return Response
     *
     * @Route("/send-email", name="oro_user_reset_send_email")
     * @Method({"POST"})
     */
    public function sendEmailAction()
    {
        $username = $this->getRequest()->request->get('username');
        $user = $this->get('oro_user.manager')->findUserByUsernameOrEmail($username);

        if (null === $user) {
            return $this->render('OroUserBundle:Reset:request.html.twig', array('invalid_username' => $username));
        }

        $passwordManager = $this->get('oro_user.security.password_manager');
        $isMessageSent = $passwordManager->setResetPasswordEmail($user);

        if (!$isMessageSent) {
            $this->get('session')->getFlashBag()->add(
                'warn',
                $passwordManager->getError()
            );

            return $this->redirect($this->generateUrl('oro_user_reset_request'));
        }

        return $this->redirect($this->generateUrl('oro_user_reset_check_email'));
    }

    /**
     * @param User $user
     *
     * @return array
     *
     * @Route("/send-email-as-admin/{id}", name="oro_user_reset_send_email_as_admin", requirements={"id"="\d+"})
     * @AclAncestor("password_management")
     * @Template("OroUserBundle:Reset/widget:sendEmailConfirmation.html.twig")
     */
    public function sendEmailAsAdminAction(User $user)
    {
        $params = [
            'entity' => $user,
        ];

        if ($this->getRequest()->isMethod('POST')) {
            $passwordManager = $this->get('oro_user.security.password_manager');
            $isMessageSent = $passwordManager->setResetPasswordEmail($user, false);

            if ($isMessageSent) {
                $params['processed'] = true;
            } else {
                $params['processed'] = false;
                $params['error'] = $passwordManager->getError();
            }
        } else {
            $params['formAction'] = $this->get('router')->generate(
                'oro_user_reset_send_email_as_admin',
                ['id' => $user->getId()]
            );
        }

        return $params;
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
     * @param string $token
     *
     * @return array
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
            $session->getFlashBag()->add('success', 'Your password has been successfully reset. You may login now.');

            // force user logout
            $session->invalidate();
            $this->get('security.context')->setToken(null);

            return $this->redirect($this->generateUrl('oro_user_security_login'));
        }

        return array(
            'token' => $token,
            'form'  => $this->get('oro_user.form.reset')->createView(),
        );
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
}
