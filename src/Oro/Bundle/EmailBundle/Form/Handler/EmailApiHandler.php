<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\Form\Model\EmailApi as EmailModel;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EmailApiHandler extends ApiFormHandler
{
    /** @var EmailEntityBuilder */
    protected $emailEntityBuilder;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var EmailOrigin|null */
    protected $emailOrigin;

    /**
     * @param FormInterface            $form
     * @param Request                  $request
     * @param EntityManager            $entityManager
     * @param EmailEntityBuilder       $emailEntityBuilder
     * @param SecurityFacade           $securityFacade
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        EntityManager $entityManager,
        EmailEntityBuilder $emailEntityBuilder,
        SecurityFacade $securityFacade,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($form, $request, $entityManager);

        $this->emailEntityBuilder = $emailEntityBuilder;
        $this->securityFacade     = $securityFacade;
        $this->eventDispatcher    = $eventDispatcher;
    }

    /**
     * @param Email $entity
     *
     * @return EmailModel
     */
    protected function prepareFormData($entity)
    {
        return parent::prepareFormData(new EmailModel($entity));
    }

    /**
     * @param EmailModel $entity
     *
     * @return Email
     */
    protected function onSuccess($entity)
    {
        $this->processEmailModel($entity);
        $this->entityManager->flush();

        if ($entity->getBody()) {
            $this->eventDispatcher->dispatch(
                EmailBodyAdded::NAME,
                new EmailBodyAdded($entity->getEntity())
            );
        }

        return $entity->getEntity();
    }

    /**
     * @param EmailModel $model
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processEmailModel(EmailModel $model)
    {
        $this->assertModel($model);

        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $messageDate = $model->getSentAt() ?: $currentDate;

        $this->ensureEmailEntitySet($model);

        $email = $model->getEntity();
        $this->emailEntityBuilder->setObject($email);

        // Folder
        if ($model->getFolders()) {
            $this->processFolders($email, $model->getFolders());
        }
        // Subject
        if ($model->getSubject()) {
            $email->setSubject($model->getSubject());
        }
        // From
        if ($model->getFrom()) {
            $this->processSender($email, $model->getFrom());
        }
        // To
        if ($model->getTo()) {
            $this->processRecipients($email, EmailRecipient::TO, $model->getTo());
        }
        // Cc
        if ($model->getCc()) {
            $this->processRecipients($email, EmailRecipient::CC, $model->getCc());
        }
        // Bcc
        if ($model->getBcc()) {
            $this->processRecipients($email, EmailRecipient::BCC, $model->getBcc());
        }
        // Body
        if ($model->getBody()) {
            $this->processBody($email, $model->getBody(), $model->getBodyType());
        } elseif ($model->getBodyType()) {
            $this->processBodyType($email, $model->getBodyType());
        }
        // CreatedAt
        if ($model->getCreatedAt()) {
            $email->setCreated($model->getCreatedAt());
        } elseif (!$email->getId()) {
            $email->setCreated($messageDate);
        }
        // SentAt
        if ($model->getSentAt()) {
            $email->setSentAt($model->getSentAt());
        } elseif (!$email->getId()) {
            $email->setSentAt($messageDate);
        }
        // ReceivedAt
        if ($model->getReceivedAt()) {
            $email->setReceivedAt($model->getReceivedAt());
        } elseif (!$email->getId()) {
            $email->setReceivedAt($messageDate);
        }
        // InternalDate
        if ($model->getInternalDate()) {
            $email->setInternalDate($model->getInternalDate());
        } elseif (!$email->getId()) {
            $email->setInternalDate($messageDate);
        }
        // Importance
        if ($model->getImportance()) {
            $email->setImportance($model->getImportance());
        }
        // Head
        if (null !== $model->isHead()) {
            $email->setHead($model->isHead());
        }
        // Seen
        if (null !== $model->isSeen()) {
            $email->setSeen($model->isSeen());
        }
        // MessageId
        if ($model->getMessageId()) {
            $email->setMessageId($model->getMessageId());
        }
        // XMessageId
        if (null !== $model->getXMessageId()) {
            $email->setXMessageId($model->getXMessageId());
        }
        // XThreadId
        if (null !== $model->getXThreadId()) {
            $email->setXThreadId($model->getXThreadId());
        }
        // Thread
        if (null !== $model->getThread()) {
            $email->setThread($model->getThread());
        }
        // Refs
        if (null !== $model->getRefs()) {
            $email->setRefs($model->getRefs());
        }

        $this->emailEntityBuilder->getBatch()->persist($this->entityManager);
    }

    /**
     * @param EmailModel $model
     *
     * @throws \InvalidArgumentException
     */
    protected function assertModel(EmailModel $model)
    {
        if ($model->getEntity() && $model->getEntity()->getId()) {
            return;
        }

        if (!$model->getFolders()) {
            throw new \InvalidArgumentException('Folders should not be empty');
        }
        if (!$model->getMessageId()) {
            throw new \InvalidArgumentException('Message-ID should not be empty');
        }
        if (!$model->getFrom()) {
            throw new \InvalidArgumentException('Sender should not be empty');
        }
        if (!$model->getTo() && !$model->getCc() && !$model->getBcc()) {
            throw new \InvalidArgumentException('Recipient should not be empty');
        }
    }

    /**
     * @param EmailModel $model
     */
    protected function ensureEmailEntitySet(EmailModel $model)
    {
        if ($model->getEntity() && $model->getEntity()->getId()) {
            return;
        }

        /** @var EmailRepository $repo */
        $repo   = $this->entityManager->getRepository('Oro\Bundle\EmailBundle\Entity\Email');
        $entity = $repo->findEmailByMessageId($model->getMessageId());
        if ($entity) {
            $model->setEntity($entity);
        }
        if (!$model->getEntity()) {
            $model->setEntity(new Email());
        }
    }

    /**
     * @return EmailOrigin
     */
    protected function getEmailOrigin()
    {
        if (!$this->emailOrigin) {
            /** @var User $originOwner */
            $originOwner = $this->securityFacade->getLoggedUser();
            $originName  = 'API_User_' . $originOwner->getId();

            $origins = $originOwner->getEmailOrigins()->filter(
                function ($item) use ($originName) {
                    return
                        $item instanceof InternalEmailOrigin
                        && $item->getName() === $originName;
                }
            );

            $this->emailOrigin = !$origins->isEmpty()
                ? $origins->first()
                : $this->createEmailOrigin($originOwner, $originName);
        }

        return $this->emailOrigin;
    }

    /**
     * @param User   $originOwner
     * @param string $originName
     *
     * @return InternalEmailOrigin
     */
    protected function createEmailOrigin(User $originOwner, $originName)
    {
        $origin = new InternalEmailOrigin();
        $origin->setName($originName);

        $originOwner->addEmailOrigin($origin);

        $this->entityManager->persist($origin);

        return $origin;
    }

    /**
     * @param Email $email
     * @param array $folders
     */
    protected function processFolders(Email $email, $folders)
    {
        foreach ($folders as $item) {
            $origin = $item['origin'] ?: $this->getEmailOrigin();
            $folder = $origin->getFolder($item['type'], $item['fullName']);
            if (!$folder) {
                $folder = $this->emailEntityBuilder->folder($item['type'], $item['fullName'], $item['name']);
                $origin->addFolder($folder);
            } else {
                $this->emailEntityBuilder->setFolder($folder);
            }

            $email->addFolder($folder);
        }
    }

    /**
     * @param Email  $email
     * @param string $sender
     */
    protected function processSender(Email $email, $sender)
    {
        $email
            ->setFromName($sender)
            ->setFromEmailAddress($this->emailEntityBuilder->address($sender));
    }

    /**
     * @param Email    $email
     * @param string   $type
     * @param string[] $recipients
     */
    protected function processRecipients(Email $email, $type, array $recipients)
    {
        foreach ($recipients as $recipient) {
            $email->addRecipient($this->emailEntityBuilder->recipient($type, $recipient));
        }
    }

    /**
     * @param Email  $email
     * @param string $content
     * @param string $type
     */
    protected function processBody(Email $email, $content, $type)
    {
        $body = $email->getEmailBody();
        if ($body) {
            $body
                ->setBodyContent($content)
                ->setBodyIsText($type === true);
        } else {
            $email->setEmailBody($this->emailEntityBuilder->body($content, $type !== true, true));
        }
    }

    /**
     * @param Email  $email
     * @param string $type
     */
    protected function processBodyType(Email $email, $type)
    {
        $body = $email->getEmailBody();
        if ($body) {
            $body->setBodyIsText($type === true);
        } else {
            $email->setEmailBody($this->emailEntityBuilder->body('', $type !== true, true));
        }
    }
}
