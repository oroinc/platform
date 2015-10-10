<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRepository;
use Oro\Bundle\EmailBundle\Event\EmailBodyAdded;
use Oro\Bundle\EmailBundle\Form\Model\EmailApi as EmailModel;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\UserBundle\Entity\User;

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

    /** @var DataTransformerInterface */
    protected $emailImportanceTransformer;

    /** @var DataTransformerInterface */
    protected $emailBodyTypeTransformer;

    /** @var EmailOrigin|null */
    protected $emailOrigin;

    /**
     * @param FormInterface            $form
     * @param Request                  $request
     * @param EntityManager            $entityManager
     * @param EmailEntityBuilder       $emailEntityBuilder
     * @param SecurityFacade           $securityFacade
     * @param EventDispatcherInterface $eventDispatcher
     * @param DataTransformerInterface $emailImportanceTransformer
     * @param DataTransformerInterface $emailBodyTypeTransformer
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        EntityManager $entityManager,
        EmailEntityBuilder $emailEntityBuilder,
        SecurityFacade $securityFacade,
        EventDispatcherInterface $eventDispatcher,
        DataTransformerInterface $emailImportanceTransformer,
        DataTransformerInterface $emailBodyTypeTransformer
    ) {
        parent::__construct($form, $request, $entityManager);

        $this->emailEntityBuilder         = $emailEntityBuilder;
        $this->securityFacade             = $securityFacade;
        $this->eventDispatcher            = $eventDispatcher;
        $this->emailImportanceTransformer = $emailImportanceTransformer;
        $this->emailBodyTypeTransformer   = $emailBodyTypeTransformer;
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function processEmailModel(EmailModel $model)
    {
        /**
         * TODO EmailEntityBuilder::email or EmailEntityBuilder::emailUser should be user here
         */
        $this->assertModel($model);

        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $messageDate = $model->getSentAt() ?: $currentDate;

        $this->ensureEmailEntitySet($model);

        $email = $model->getEntity();

        // Subject
        if ($model->getSubject() || !$email->getId()) {
            $this->processString($email, 'Subject', (string)$model->getSubject());
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
        if ($model->getBody() || !$email->getId()) {
            $this->processBody($email, $model->getBody(), $model->getBodyType());
        } elseif (null !== $model->getBodyType()) {
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
        // InternalDate
        if ($model->getInternalDate()) {
            $email->setInternalDate($model->getInternalDate());
        } elseif (!$email->getId()) {
            $email->setInternalDate($messageDate);
        }
        // Importance
        if (null !== $model->getImportance()) {
            $this->processImportance($email, $model->getImportance());
        }
        // Head
        if (null !== $model->isHead()) {
            $this->processHead($email, $model->isHead());
        }
        // MessageId
        if (null !== $model->getMessageId()) {
            $this->processString($email, 'MessageId', $model->getMessageId());
        }
        // XMessageId
        if (null !== $model->getXMessageId()) {
            $this->processString($email, 'XMessageId', $model->getXMessageId());
        }
        // XThreadId
        if (null !== $model->getXThreadId()) {
            $this->processString($email, 'XThreadId', $model->getXThreadId());
        }
        // Thread
        if (null !== $model->getThread()) {
            $this->processThread($email, $model->getThread());
        }
        // Refs
        if (null !== $model->getRefs()) {
            $this->processRefs($email, $model->getRefs());
        }

        // process EmailUser entities for each folder
        $emailUsers = $this->processFolders($email, $model->getFolders());
        foreach ($emailUsers as $emailUser) {
            // ReceivedAt
            if ($model->getReceivedAt()) {
                $emailUser->setReceivedAt($model->getReceivedAt());
            } elseif (!$email->getId()) {
                $emailUser->setReceivedAt($messageDate);
            }
            // Seen
            if (null !== $model->isSeen()) {
                $emailUser->setSeen($model->isSeen());
            }
            $this->emailEntityBuilder->setObject($emailUser);
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
            throw new \InvalidArgumentException('Recipients should not be empty');
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
            $organization = $this->securityFacade->getOrganization();
            $originName   = InternalEmailOrigin::BAP . '_User_' . $originOwner->getId();

            $origins = $originOwner->getEmailOrigins()->filter(
                function ($item) use ($originName, $organization) {
                    return
                        $item instanceof InternalEmailOrigin
                        && $item->getOrganization() === $organization;
                }
            );

            $this->emailOrigin = !$origins->isEmpty()
                ? $origins->first()
                : $this->createEmailOrigin($originOwner, $organization, $originName);
        }

        return $this->emailOrigin;
    }

    /**
     * @param User                  $originOwner
     * @param OrganizationInterface $organization
     * @param string                $originName
     *
     * @return InternalEmailOrigin
     */
    protected function createEmailOrigin(User $originOwner, OrganizationInterface $organization, $originName)
    {
        $origin = new InternalEmailOrigin();
        $origin->setName($originName);
        $origin->setOwner($originOwner);
        $origin->setOrganization($organization);

        $originOwner->addEmailOrigin($origin);

        $this->entityManager->persist($origin);

        return $origin;
    }

    /**
     * @param Email $email
     * @param array $folders
     *
     * @return EmailUser[]
     */
    protected function processFolders(Email $email, $folders)
    {
        $apiOrigin = $this->getEmailOrigin();
        $emailUserList = [];
        foreach ($folders as $item) {
            $origin = $item['origin'] ?: $this->getEmailOrigin();
            if ($origin->getId() && $origin->getId() !== $apiOrigin->getId()) {
                continue;
            }

            $folder = $origin->getFolder($item['type'], $item['fullName']);
            if (!$folder) {
                $folder = $this->emailEntityBuilder->folder($item['type'], $item['fullName'], $item['name']);
                $origin->addFolder($folder);
            } else {
                $this->emailEntityBuilder->setFolder($folder);
            }

            $emailUser = new EmailUser();
            $emailUser->setEmail($email);
            $emailUser->setOwner($apiOrigin->getOwner());
            $emailUser->setOrganization($apiOrigin->getOrganization());
            $emailUser->addFolder($folder);
            $emailUser->setOrigin($origin);

            $emailUserList[] = $emailUser;
        }

        return $emailUserList;
    }

    /**
     * @param Email  $email
     * @param string $property
     * @param string $value
     */
    protected function processString(Email $email, $property, $value)
    {
        if ($email->getId()) {
            if ($email->{'get' . $property}() !== $value) {
                throw $this->createInvalidPropertyException(
                    $property,
                    $email->{'get' . $property}(),
                    $value
                );
            }
        } else {
            $email->{'set' . $property}($value);
        }
    }

    /**
     * @param Email  $email
     * @param string $sender
     */
    protected function processSender(Email $email, $sender)
    {
        if ($email->getId()) {
            if (strtolower($email->getFromName()) !== strtolower($sender)) {
                throw $this->createInvalidPropertyException(
                    'Sender',
                    $email->getFromName(),
                    $sender
                );
            }
        } else {
            $email
                ->setFromName($sender)
                ->setFromEmailAddress($this->emailEntityBuilder->address($sender));
        }
    }

    /**
     * @param Email    $email
     * @param string   $type
     * @param string[] $recipients
     */
    protected function processRecipients(Email $email, $type, array $recipients)
    {
        if ($email->getId()) {
            $existingRecipients = $email->getRecipients($type);
            if (!$this->areRecipientsEqual($existingRecipients, $recipients)) {
                throw $this->createInvalidPropertyException(
                    sprintf('"%s" recipients', $type),
                    $this->convertRecipientsToString($existingRecipients),
                    $this->convertRecipientsToString($recipients)
                );
            }
        } else {
            foreach ($recipients as $recipient) {
                $email->addRecipient($this->emailEntityBuilder->recipient($type, $recipient));
            }
        }
    }

    /**
     * @param Email       $email
     * @param string|null $content
     * @param bool|null   $type
     */
    protected function processBody(Email $email, $content, $type)
    {
        $body = $email->getEmailBody();
        if ($body) {
            if ($email->getId()) {
                if ($body->getBodyContent() !== $content) {
                    throw $this->createInvalidPropertyException(
                        'Body Content',
                        $body->getBodyContent(),
                        $content
                    );
                }
            } else {
                $body->setBodyContent($content);
            }
            if ($email->getId()) {
                if ($body->getBodyIsText() !== ($type === true)) {
                    throw $this->createInvalidPropertyException(
                        'Body Type',
                        $this->emailBodyTypeTransformer->transform($body->getBodyIsText()),
                        $this->emailBodyTypeTransformer->transform($type)
                    );
                }
            } else {
                $body->setBodyIsText($type === true);
            }
        } else {
            $email->setEmailBody($this->emailEntityBuilder->body($content, $type !== true, true));
        }
    }

    /**
     * @param Email $email
     * @param bool  $type
     */
    protected function processBodyType(Email $email, $type)
    {
        $body = $email->getEmailBody();
        if ($body) {
            if ($email->getId()) {
                if ($body->getBodyIsText() !== ($type === true)) {
                    throw $this->createInvalidPropertyException(
                        'Body Type',
                        $this->emailBodyTypeTransformer->transform($body->getBodyIsText()),
                        $this->emailBodyTypeTransformer->transform($type)
                    );
                }
            } else {
                $body->setBodyIsText($type === true);
            }
        } else {
            $email->setEmailBody($this->emailEntityBuilder->body('', $type !== true, true));
        }
    }

    /**
     * @param Email  $email
     * @param string $importance
     */
    protected function processImportance(Email $email, $importance)
    {
        if ($email->getId()) {
            if ($email->getImportance() != $importance) {
                throw $this->createInvalidPropertyException(
                    'Importance',
                    $this->emailImportanceTransformer->transform($email->getImportance()),
                    $this->emailImportanceTransformer->transform($importance)
                );
            }
        } else {
            $email->setImportance($importance);
        }
    }

    /**
     * @param Email $email
     * @param bool  $head
     */
    protected function processHead(Email $email, $head)
    {
        if ($email->getId()) {
            if ($email->isHead() != $head) {
                throw $this->createInvalidPropertyException(
                    'Head',
                    $email->isHead() ? 'true' : 'false',
                    $head ? 'true' : 'false'
                );
            }
        } else {
            $email->setHead($head);
        }
    }

    /**
     * @param Email       $email
     * @param EmailThread $thread
     */
    protected function processThread(Email $email, EmailThread $thread)
    {
        if ($email->getId()) {
            if (!$email->getThread() || $email->getThread()->getId() != $thread->getId()) {
                throw $this->createInvalidPropertyException(
                    'Thread',
                    $email->getThread() ? $email->getThread()->getId() : null,
                    $thread->getId()
                );
            }
        } else {
            $email->setThread($thread);
        }
    }

    /**
     * @param Email  $email
     * @param string $refs
     */
    protected function processRefs(Email $email, $refs)
    {
        if ($email->getId()) {
            if (!$this->areRefsEqual($email->getRefs(), $refs)) {
                throw $this->createInvalidPropertyException(
                    'Refs',
                    $this->convertRefsToString($email->getRefs()),
                    $this->convertRefsToString($refs)
                );
            }
        } else {
            $email->setRefs($refs);
        }
    }

    /**
     * @param string[]|EmailRecipient[]|Collection $existingRecipients
     * @param string[]|EmailRecipient[]|Collection $newRecipients
     *
     * @return bool
     */
    protected function areRecipientsEqual($existingRecipients, $newRecipients)
    {
        if (count($existingRecipients) !== count($newRecipients)) {
            return false;
        }

        $normalizedExistingRecipients = $this->normalizeRecipients($existingRecipients);
        $normalizedNewRecipients      = $this->normalizeRecipients($newRecipients);

        for ($i = count($normalizedExistingRecipients) - 1; $i >= 0; $i--) {
            if (strtolower($normalizedExistingRecipients[$i]) !== strtolower($normalizedNewRecipients[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns alphabetically sorted array of string representation of each recipient in the given list
     *
     * @param string[]|EmailRecipient[]|Collection $recipients
     *
     * @return string[]
     */
    protected function normalizeRecipients($recipients)
    {
        if ($recipients instanceof Collection) {
            $recipients = $recipients->toArray();
        }
        if (reset($recipients) instanceof EmailRecipient) {
            $recipients = array_map(
                function (EmailRecipient $recipient) {
                    return $recipient->getName();
                },
                $recipients
            );
        }
        sort($recipients, SORT_STRING | SORT_FLAG_CASE);

        return array_values($recipients);
    }

    /**
     * Returns human readable representation of the recipient list
     *
     * @param string[]|EmailRecipient[]|Collection $recipients
     *
     * @return string
     */
    protected function convertRecipientsToString($recipients)
    {
        return implode('; ', $this->normalizeRecipients($recipients));
    }

    /**
     * @param string[]|string $existingRefs
     * @param string[]|string $newRefs
     *
     * @return bool
     */
    protected function areRefsEqual($existingRefs, $newRefs)
    {
        $normalizedExistingRefs = $this->normalizeRefs($existingRefs);
        $normalizedNewRefs      = $this->normalizeRefs($newRefs);

        if (count($normalizedExistingRefs) !== count($normalizedNewRefs)) {
            return false;
        }

        for ($i = count($normalizedExistingRefs) - 1; $i >= 0; $i--) {
            if (strtolower($normalizedExistingRefs[$i]) !== strtolower($normalizedNewRefs[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns alphabetically sorted array of string representation of each reference in the given list
     *
     * @param string[]|string $refs
     *
     * @return string[]
     */
    protected function normalizeRefs($refs)
    {
        if (is_string($refs)) {
            $matches = [];
            if ($refs) {
                preg_match_all('/<(.+?)>/is', $refs, $matches);
                if (empty($matches[0])) {
                    $matches[0] = [$refs];
                }
                $refs = $matches[0];
            }
        }
        sort($refs, SORT_STRING | SORT_FLAG_CASE);

        return array_values($refs);
    }

    /**
     * Returns human readable representation of the reference list
     *
     * @param string[]|string $refs
     *
     * @return string
     */
    protected function convertRefsToString($refs)
    {
        return implode('', $this->normalizeRefs($refs));
    }

    /**
     * @param string $property
     * @param mixed  $existingValue
     * @param mixed  $newValue
     *
     * @return \InvalidArgumentException
     */
    protected function createInvalidPropertyException($property, $existingValue, $newValue)
    {
        return new \InvalidArgumentException(
            sprintf(
                'The %s cannot be changed for already existing email.'
                . ' Existing value: "%s". New value: "%s".',
                $property,
                $existingValue,
                $newValue
            )
        );
    }
}
