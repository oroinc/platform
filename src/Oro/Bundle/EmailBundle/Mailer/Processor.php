<?php

namespace Oro\Bundle\EmailBundle\Mailer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Util\EmailUtil;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\UserBundle\Entity\User;

class Processor
{
    /** @var EntityManager */
    protected $em;

    /** @var  DoctrineHelper */
    protected $doctrineHelper;

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var EmailEntityBuilder */
    protected $emailEntityBuilder;

    /** @var  EmailOwnerProvider */
    protected $emailOwnerProvider;

    /** @var  ConfigProvider */
    protected $activityConfigProvider;

    /**
     * @param DoctrineHelper     $doctrineHelper
     * @param \Swift_Mailer      $mailer
     * @param EmailEntityBuilder $emailEntityBuilder
     * @param EmailOwnerProvider $emailOwnerProvider
     * @param ConfigProvider     $activityConfigProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        \Swift_Mailer $mailer,
        EmailEntityBuilder $emailEntityBuilder,
        EmailOwnerProvider $emailOwnerProvider,
        ConfigProvider $activityConfigProvider
    ) {
        $this->doctrineHelper         = $doctrineHelper;
        $this->mailer                 = $mailer;
        $this->emailEntityBuilder     = $emailEntityBuilder;
        $this->emailOwnerProvider     = $emailOwnerProvider;
        $this->activityConfigProvider = $activityConfigProvider;
    }

    /**
     * Process email model sending.
     *
     * @param EmailModel $model
     * @return Email
     * @throws \Swift_SwiftException
     */
    public function process(EmailModel $model)
    {
        $this->assertModel($model);
        $messageDate = new \DateTime('now', new \DateTimeZone('UTC'));

        /** @var \Swift_Message $message */
        $message = $this->mailer->createMessage();
        $message->setDate($messageDate->getTimestamp());
        $message->setFrom($this->getAddresses($model->getFrom()));
        $message->setTo($this->getAddresses($model->getTo()));
        $message->setSubject($model->getSubject());
        $message->setBody($model->getBody(), 'text/plain');

        $messageId = $message->generateId();

        if (!$this->mailer->send($message)) {
            throw new \Swift_SwiftException('An email was not delivered.');
        }

        $origin = $this->getEmailOrigin($model->getFrom());
        $this->emailEntityBuilder->setOrigin($origin);

        $email = $this->emailEntityBuilder->email(
            $model->getSubject(),
            $model->getFrom(),
            $model->getTo(),
            $messageDate,
            $messageDate,
            $messageDate
        );

        $email->addFolder($origin->getFolder(EmailFolder::SENT));
        $email->setEmailBody($this->emailEntityBuilder->body($model->getBody(), false, true));
        $email->setMessageId($messageId);

        $this->emailEntityBuilder->getBatch()->persist($this->getEntityManager());
        $this->getEntityManager()->flush();

        $this->addAssociations($model, $email);

        return $email;
    }

    /**
     * Find existing email origin entity by email string or create and persist new one.
     *
     * @param string $email
     * @param string $originName
     * @return EmailOrigin
     */
    public function getEmailOrigin($email, $originName = InternalEmailOrigin::BAP)
    {
        $emailOwner = $this->emailOwnerProvider->findEmailOwner(
            $this->getEntityManager(),
            EmailUtil::extractPureEmailAddress($email)
        );

        if ($emailOwner instanceof User) {
            $origins = $emailOwner->getEmailOrigins()->filter(
                function ($item) {
                    return $item instanceof InternalEmailOrigin;
                }
            );

            $origin = $origins->isEmpty() ? null : $origins->first();
            if ($origin == null) {
                $origin = $this->createUserInternalOrigin($emailOwner);
            }
        } else {
            $origin = $this->getEntityManager()
                ->getRepository('OroEmailBundle:InternalEmailOrigin')
                ->findOneBy(array('internalName' => $originName));
        }

        return $origin;
    }

    /**
     * @param User $emailOwner
     * @return InternalEmailOrigin
     */
    protected function createUserInternalOrigin(User $emailOwner)
    {
        $originName = InternalEmailOrigin::BAP . '_User_' . $emailOwner->getId();

        $outboxFolder = new EmailFolder();
        $outboxFolder
            ->setType(EmailFolder::SENT)
            ->setName(EmailFolder::SENT)
            ->setFullName(EmailFolder::SENT);

        $origin = new InternalEmailOrigin();
        $origin
            ->setName($originName)
            ->addFolder($outboxFolder);

        $emailOwner->addEmailOrigin($origin);

        $this->getEntityManager()->persist($origin);
        $this->getEntityManager()->persist($emailOwner);

        return $origin;
    }

    /**
     * @param EmailModel $model
     * @throws \InvalidArgumentException
     */
    protected function assertModel(EmailModel $model)
    {
        if (!$model->getFrom()) {
            throw new \InvalidArgumentException('Sender can not be empty');
        }
        if (!$model->getTo()) {
            throw new \InvalidArgumentException('Recipient can not be empty');
        }
    }

    /**
     * Converts emails addresses to a form acceptable to \Swift_Mime_Message class
     *
     * @param string|string[] $addresses Examples of correct email addresses: john@example.com, <john@example.com>,
     *                                   John Smith <john@example.com> or "John Smith" <john@example.com>
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getAddresses($addresses)
    {
        $result = array();

        if (is_string($addresses)) {
            $addresses = array($addresses);
        }
        if (!is_array($addresses) && !$addresses instanceof \Iterator) {
            throw new \InvalidArgumentException(
                'The $addresses argument must be a string or a list of strings (array or Iterator)'
            );
        }

        foreach ($addresses as $address) {
            $name = EmailUtil::extractEmailAddressName($address);
            if (empty($name)) {
                $result[] = EmailUtil::extractPureEmailAddress($address);
            } else {
                $result[EmailUtil::extractPureEmailAddress($address)] = $name;
            }
        }

        return $result;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (null === $this->em) {
            $this->em = $this->doctrineHelper->getEntityManager('OroEmailBundle:Email');
        }

        return $this->em;
    }

    /**
     * @param EmailModel $model
     * @param Email      $email
     */
    protected function addAssociations(EmailModel $model, Email $email)
    {
        $targetEntityClass = $model->getEntityClass();
        if (empty($targetEntityClass)) {
            return;
        }
        $targetEntityId = $model->getEntityId();
        if (empty($targetEntityId)) {
            return;
        }

        // prepare the list of association targets
        $targets      = [];
        $targetEntity = $this->doctrineHelper->getEntity($targetEntityClass, $targetEntityId);
        if ($targetEntity) {
            $targets[] = $targetEntity;
        }
        $this->addEmailRecipientOwnersToAssociationTargets($targets, $email);

        // add associations
        $hasChanges = false;
        $emailClass = ClassUtils::getClass($email);
        foreach ($targets as $target) {
            $targetClass = ClassUtils::getClass($target);
            if (!$this->activityConfigProvider->hasConfig($targetClass)) {
                continue;
            }
            $config     = $this->activityConfigProvider->getConfig($targetClass);
            $activities = $config->get('activities');
            if (empty($activities) || !in_array($emailClass, $activities)) {
                continue;
            }
            $email->addActivityTarget($target);
            $hasChanges = true;
        }

        // flush if needed
        if ($hasChanges) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param array $targets
     * @param Email $email
     */
    protected function addEmailRecipientOwnersToAssociationTargets(&$targets, Email $email)
    {
        $recipients = $email->getRecipients();
        foreach ($recipients as $recipient) {
            $emailOwner = $recipient->getEmailAddress()->getOwner();
            if (!$emailOwner) {
                continue;
            }
            $alreadyExists = false;
            foreach ($targets as $target) {
                if ($emailOwner === $target) {
                    $alreadyExists = true;
                    break;
                }
            }
            if (!$alreadyExists) {
                $targets[] = $emailOwner;
            }
        }
    }
}
