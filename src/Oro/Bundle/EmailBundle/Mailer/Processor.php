<?php

namespace Oro\Bundle\EmailBundle\Mailer;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Util\EmailUtil;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\UserBundle\Entity\User;

class Processor
{
    /** @var EntityManager */
    protected $em;

    /** @var EmailEntityBuilder */
    protected $emailEntityBuilder;

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var  EmailOwnerProvider */
    protected $emailOwnerProvider;

    /**
     * @param EntityManager      $em
     * @param EmailEntityBuilder $emailEntityBuilder
     * @param \Swift_Mailer      $mailer
     * @param EmailOwnerProvider $emailOwnerProvider
     */
    public function __construct(
        EntityManager $em,
        EmailEntityBuilder $emailEntityBuilder,
        \Swift_Mailer $mailer,
        EmailOwnerProvider $emailOwnerProvider
    ) {
        $this->em                 = $em;
        $this->emailEntityBuilder = $emailEntityBuilder;
        $this->mailer             = $mailer;
        $this->emailOwnerProvider = $emailOwnerProvider;
    }

    /**
     * Process email model sending.
     *
     * @param Email  $model
     * @param string $originName
     * @return \Oro\Bundle\EmailBundle\Entity\Email
     * @throws \Swift_SwiftException
     */
    public function process(Email $model, $originName = InternalEmailOrigin::BAP)
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

        $messageId = $messageDate->format('U') . '.' . uniqid('id_') . '@' . gethostname();
        $message->getHeaders()->addIdHeader('Message-ID', $messageId);

        if (!$this->mailer->send($message)) {
            throw new \Swift_SwiftException('An email was not delivered.');
        }

        $emailOwner = $this->emailOwnerProvider->findEmailOwner(
            $this->em,
            EmailUtil::extractPureEmailAddress($model->getFrom())
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
            $origin = $this->em
                ->getRepository('OroEmailBundle:InternalEmailOrigin')
                ->findOneBy(array('internalName' => $originName));
        }

        $this->emailEntityBuilder->setOrigin($origin);

        $email = $this->emailEntityBuilder->email(
            $model->getSubject(),
            $model->getFrom(),
            $model->getTo(),
            $messageDate,
            $messageDate,
            $messageDate
        );

        $email->setFolder($origin->getFolder(EmailFolder::SENT));
        $email->setEmailBody($this->emailEntityBuilder->body($model->getBody(), false, true));
        $email->setMessageId($messageId);

        $this->emailEntityBuilder->getBatch()->persist($this->em);
        $this->em->flush();

        return $email;
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

        $this->em->persist($origin);
        $this->em->persist($emailOwner);

        return $origin;
    }

    /**
     * @param Email $model
     * @throws \InvalidArgumentException
     */
    protected function assertModel(Email $model)
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
}
