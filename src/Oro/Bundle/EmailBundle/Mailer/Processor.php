<?php

namespace Oro\Bundle\EmailBundle\Mailer;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;

class Processor
{
    /** @var EntityManager */
    protected $em;

    /** @var  DoctrineHelper */
    protected $doctrineHelper;

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    /** @var EmailEntityBuilder */
    protected $emailEntityBuilder;

    /** @var  EmailOwnerProvider */
    protected $emailOwnerProvider;

    /** @var  EmailActivityManager */
    protected $emailActivityManager;

    /** @var array */
    protected $origins = array();

    /**
     * @param DoctrineHelper       $doctrineHelper
     * @param \Swift_Mailer        $mailer
     * @param EmailAddressHelper   $emailAddressHelper
     * @param EmailEntityBuilder   $emailEntityBuilder
     * @param EmailOwnerProvider   $emailOwnerProvider
     * @param EmailActivityManager $emailActivityManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        \Swift_Mailer $mailer,
        EmailAddressHelper $emailAddressHelper,
        EmailEntityBuilder $emailEntityBuilder,
        EmailOwnerProvider $emailOwnerProvider,
        EmailActivityManager $emailActivityManager
    ) {
        $this->doctrineHelper       = $doctrineHelper;
        $this->mailer               = $mailer;
        $this->emailAddressHelper   = $emailAddressHelper;
        $this->emailEntityBuilder   = $emailEntityBuilder;
        $this->emailOwnerProvider   = $emailOwnerProvider;
        $this->emailActivityManager = $emailActivityManager;
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
        $message->setBody($model->getBody(), $model->getType() === 'html' ? 'text/html' : 'text/plain');

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

        $email->addFolder($origin->getFolder(FolderType::SENT));
        $email->setEmailBody($this->emailEntityBuilder->body($model->getBody(), $model->getType() === 'html', true));
        $email->setMessageId($messageId);

        // persist the email and all related entities such as folders, email addresses etc.
        $this->emailEntityBuilder->getBatch()->persist($this->getEntityManager());

        // associate the email with the target entity if exist
        if ($model->hasEntity()) {
            $targetEntity = $this->doctrineHelper->getEntity($model->getEntityClass(), $model->getEntityId());
            if ($targetEntity) {
                $this->emailActivityManager->addAssociation($email, $targetEntity);
            }
        }

        // flush all changes to the database
        $this->getEntityManager()->flush();

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
        $originKey = $originName . $email;
        if (!array_key_exists($originKey, $this->origins)) {
            $emailOwner = $this->emailOwnerProvider->findEmailOwner(
                $this->getEntityManager(),
                $this->emailAddressHelper->extractPureEmailAddress($email)
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
            $this->origins[$originKey] = $origin;
        }

        return $this->origins[$originKey];
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
            ->setType(FolderType::SENT)
            ->setName(FolderType::SENT)
            ->setFullName(FolderType::SENT);

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
            $name = $this->emailAddressHelper->extractEmailAddressName($address);
            if (empty($name)) {
                $result[] = $this->emailAddressHelper->extractPureEmailAddress($address);
            } else {
                $result[$this->emailAddressHelper->extractPureEmailAddress($address)] = $name;
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
}
