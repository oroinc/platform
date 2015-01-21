<?php

namespace Oro\Bundle\UserBundle\Mailer;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class Processor
{
    /** @var ObjectManager */
    protected $objectManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EmailRenderer */
    protected $renderer;

    /** @var  UserManager */
    protected $userManager;

    /** @var \Swift_Mailer */
    protected $mailer;

    /**
     * @param ObjectManager       $objectManager
     * @param EmailRenderer       $renderer
     * @param ConfigManager       $configManager
     * @param UserManager         $userManager
     * @param \Swift_Mailer       $mailer
     */
    public function __construct(
        ObjectManager    $objectManager,
        ConfigManager    $configManager,
        EmailRenderer    $renderer,
        UserManager      $userManager,
        \Swift_Mailer    $mailer = null
    ) {
        $this->objectManager = $objectManager;
        $this->configManager = $configManager;
        $this->renderer      = $renderer;
        $this->userManager   = $userManager;
        $this->mailer        = $mailer;
    }

    /**
     * @param User $entity
     */
    public function sendEmail(User $entity)
    {
        $plainPassword = $entity->getPlainPassword();
        $entity->setPasswordChangedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->userManager->updateUser($entity);

        $emailTemplate = $this->objectManager->getRepository('OroEmailBundle:EmailTemplate')
            ->findByName('user_change_password');

        list ($subjectRendered, $templateRendered) = $this->renderer->compileMessage(
            $emailTemplate,
            ['entity' => $entity, 'plainPassword' => $plainPassword]
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
    }
}
