<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class SetPasswordHandler
{
    /**
     * @var ObjectManager
     */
    protected $om;

    /** @var  LoggerInterface */
    protected $logger;

    /** @var Request */
    protected $request;

    /** @var ConfigManager */
    protected $configManager;

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var EmailRenderer */
    protected $renderer;

    /** @var  UserManager */
    protected $userManager;

    /** @var FormInterface */
    protected $form;

    /**
     * @param ObjectManager       $om
     * @param LoggerInterface     $logger
     * @param Request             $request
     * @param EmailRenderer       $renderer
     * @param ConfigManager       $configManager
     * @param \Swift_Mailer       $mailer
     * @param UserManager         $userManager
     * @param FormInterface       $form
     */
    public function __construct(
        ObjectManager    $om,
        LoggerInterface  $logger,
        Request          $request,
        ConfigManager    $configManager,
        \Swift_Mailer    $mailer = null,
        EmailRenderer    $renderer,
        UserManager      $userManager,
        FormInterface    $form
    ) {
        $this->om            = $om;
        $this->logger        = $logger;
        $this->request       = $request;
        $this->configManager = $configManager;
        $this->mailer        = $mailer;
        $this->renderer      = $renderer;
        $this->userManager   = $userManager;
        $this->form          = $form;
    }

    /**
     * Process form
     *
     * @param  User $entity
     *
     * @return bool  True on successful processing, false otherwise
     */
    public function process(User $entity)
    {
        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                if (in_array(null, [$this->configManager, $this->mailer], true)) {
                    throw new \RuntimeException('Unable to send invitation email, unmet dependencies detected.');
                }

                $entity->setPlainPassword($this->form->get('password')->getData());
                $this->userManager->updateUser($entity);

                $emailTemplate = $this->om->getRepository('OroEmailBundle:EmailTemplate')->findByName('user_change_password');

                try {
                    list ($subjectRendered, $templateRendered) = $this->renderer->compileMessage(
                        $emailTemplate,
                        [
                            'entity' => $entity,
                            'plainPassword' => $this->form->get('password')->getData(),
                        ]
                    );

                    $senderEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
                    $senderName  = $this->configManager->get('oro_notification.email_notification_sender_name');
                    $type        = $emailTemplate->getType() == 'txt' ? 'text/plain' : 'text/html';
                    $message     = \Swift_Message::newInstance()
                        ->setSubject($subjectRendered)
                        ->setFrom($senderEmail, $senderName)
                        ->setTo($entity->getEmail())
                        ->setBody($templateRendered, $type);
                    $this->mailer->send($message);

                    return true;
                } catch (\Twig_Error $e) {
                    $identity = method_exists($emailTemplate, '__toString')
                        ? (string)$emailTemplate : $emailTemplate->getSubject();

                    $this->logger->error(
                        sprintf('Rendering of email template "%s" failed. %s', $identity, $e->getMessage()),
                        ['exception' => $e]
                    );
                }
            }
        }

        return false;
    }
}
