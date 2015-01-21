<?php

namespace Oro\Bundle\UserBundle\Form\Handler;

use Psr\Log\LoggerInterface;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;

class SetPasswordHandler
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /** @var  LoggerInterface */
    protected $logger;

    /** @var Request */
    protected $request;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EmailRenderer */
    protected $renderer;

    /** @var  UserManager */
    protected $userManager;

    /** @var FormInterface */
    protected $form;

    /** @var \Swift_Mailer */
    protected $mailer;

    /**
     * @param ObjectManager       $objectManager
     * @param LoggerInterface     $logger
     * @param Request             $request
     * @param EmailRenderer       $renderer
     * @param ConfigManager       $configManager
     * @param UserManager         $userManager
     * @param FormInterface       $form
     * @param \Swift_Mailer       $mailer
     */
    public function __construct(
        ObjectManager    $objectManager,
        LoggerInterface  $logger,
        Request          $request,
        ConfigManager    $configManager,
        EmailRenderer    $renderer,
        UserManager      $userManager,
        FormInterface    $form,
        \Swift_Mailer    $mailer = null
    ) {
        $this->objectManager = $objectManager;
        $this->logger        = $logger;
        $this->request       = $request;
        $this->configManager = $configManager;
        $this->renderer      = $renderer;
        $this->userManager   = $userManager;
        $this->form          = $form;
        $this->mailer        = $mailer;
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
                $plainPassword = $this->form->get('password')->getData();

                $emailTemplate = $this->objectManager->getRepository('OroEmailBundle:EmailTemplate')
                    ->findByName('user_change_password');

                try {
                    $this->sendEmail($entity, $plainPassword, $emailTemplate);
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

    /**
     * @param User $entity
     * @param string $plainPassword
     * @param EmailTemplateInterface $emailTemplate
     */
    protected function sendEmail(User $entity, $plainPassword, $emailTemplate)
    {
        list ($subjectRendered, $templateRendered) = $this->renderer->compileMessage(
            $emailTemplate,
            [
                'entity' => $entity,
                'plainPassword' => $plainPassword,
            ]
        );

        $senderEmail = $this->configManager->get('oro_notification.email_notification_sender_email');
        $senderName = $this->configManager->get('oro_notification.email_notification_sender_name');
        $type = $emailTemplate->getType() == 'txt' ? 'text/plain' : 'text/html';
        $message = \Swift_Message::newInstance()
            ->setSubject($subjectRendered)
            ->setFrom($senderEmail, $senderName)
            ->setTo($entity->getEmail())
            ->setBody($templateRendered, $type);
        $this->mailer->send($message);
    }
}
