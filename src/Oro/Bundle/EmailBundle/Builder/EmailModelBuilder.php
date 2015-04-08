<?php

namespace Oro\Bundle\EmailBundle\Builder;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;

/**
 * Class EmailModelBuilder
 *
 * @package Oro\Bundle\EmailBundle\Builder
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class EmailModelBuilder
{
    /**
     * @var EmailModelBuilderHelper
     */
    protected $helper;
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var EmailActivityListProvider
     */
    protected $activityListProvider;

    /**
     * @param EmailModelBuilderHelper   $emailModelBuilderHelper
     * @param Request                   $request
     * @param EntityManager             $entityManager
     * @param ConfigManager             $configManager
     * @param EmailActivityListProvider $activityListProvider
     */
    public function __construct(
        EmailModelBuilderHelper $emailModelBuilderHelper,
        Request $request,
        EntityManager $entityManager,
        ConfigManager $configManager,
        EmailActivityListProvider $activityListProvider
    ) {
        $this->helper               = $emailModelBuilderHelper;
        $this->request              = $request;
        $this->entityManager        = $entityManager;
        $this->configManager        = $configManager;
        $this->activityListProvider = $activityListProvider;
    }

    /**
     * @param EmailModel $emailModel
     *
     * @return EmailModel
     */
    public function createEmailModel(EmailModel $emailModel = null)
    {
        if (!$emailModel) {
            $emailModel = new EmailModel();
        }

        if ($this->request->getMethod() === 'GET') {
            $this->applyRequest($emailModel);
        }
        $this->applySignature($emailModel);

        return $emailModel;
    }

    /**
     * @param EmailEntity $parentEmailEntity
     *
     * @return EmailModel
     */
    public function createReplyEmailModel(EmailEntity $parentEmailEntity)
    {
        $emailModel = new EmailModel();

        $emailModel->setParentEmailId($parentEmailEntity->getId());

        $fromAddress = $parentEmailEntity->getFromEmailAddress();
        if ($fromAddress->getOwner() == $this->helper->getUser()) {
            $emailModel->setTo([$parentEmailEntity->getTo()->first()->getEmailAddress()->getEmail()]);
            $emailModel->setFrom($fromAddress->getEmail());
        } else {
            $emailModel->setTo([$fromAddress->getEmail()]);
            $this->initReplyFrom($emailModel, $parentEmailEntity);
        }

        $emailModel->setSubject($this->helper->prependWith('Re: ', $parentEmailEntity->getSubject()));

        $body = $this->helper->getEmailBody($parentEmailEntity, 'OroEmailBundle:Email/Reply:parentBody.html.twig');
        $emailModel->setBodyFooter($body);
        $emailModel->setContexts($this->activityListProvider->getTargetEntities($parentEmailEntity));

        return $this->createEmailModel($emailModel);
    }

    /**
     * @param EmailModel  $emailModel
     * @param EmailEntity $parentEmailEntity
     */
    protected function initReplyFrom(EmailModel $emailModel, EmailEntity $parentEmailEntity)
    {
        $userEmails = $this->helper->getUser()->getEmails();
        $toEmails = [];
        $emailRecipients = $parentEmailEntity->getTo();
        /** @var EmailRecipient $emailRecipient */
        foreach ($emailRecipients as $emailRecipient) {
            $toEmails[] = $emailRecipient->getEmailAddress()->getEmail();
        }

        foreach ($userEmails as $userEmail) {
            if (in_array($userEmail->getEmail(), $toEmails)) {
                $emailModel->setFrom($userEmail->getEmail());

                break;
            }
        }
    }

    /**
     * @param EmailEntity $parentEmailEntity
     *
     * @return EmailModel
     */
    public function createForwardEmailModel(EmailEntity $parentEmailEntity)
    {
        $emailModel = new EmailModel();

        $emailModel->setParentEmailId($parentEmailEntity->getId());

        $emailModel->setSubject($this->helper->prependWith('Fwd: ', $parentEmailEntity->getSubject()));
        $body = $this->helper->getEmailBody($parentEmailEntity, 'OroEmailBundle:Email/Forward:parentBody.html.twig');
        $emailModel->setBodyFooter($body);

        return $this->createEmailModel($emailModel);
    }

    /**
     * @param EmailModel $emailModel
     */
    protected function applyRequest(EmailModel $emailModel)
    {
        $this->applyEntityData($emailModel);
        $this->applyFrom($emailModel);
        $this->applySubject($emailModel);
        $this->applyFrom($emailModel);
        $this->applyRecipients($emailModel);
    }

    /**
     * @param EmailModel $emailModel
     */
    protected function applyEntityData(EmailModel $emailModel)
    {
        if ($this->request->query->has('entityClass')) {
            $emailModel->setEntityClass(
                $this->helper->decodeClassName($this->request->query->get('entityClass'))
            );
        }
        if ($this->request->query->has('entityId')) {
            $emailModel->setEntityId($this->request->query->get('entityId'));
        }
        if (!$emailModel->getEntityClass() || !$emailModel->getEntityId()) {
            if ($emailModel->getParentEmailId()) {
                $parentEmail = $this->entityManager->getRepository('OroEmailBundle:Email')
                    ->find($emailModel->getParentEmailId());
                $this->applyEntityDataFromEmail($emailModel, $parentEmail);
            }
        }
    }

    /**
     * @param EmailModel $emailModel
     */
    protected function applyFrom(EmailModel $emailModel)
    {
        if (!$emailModel->getFrom()) {
            if ($this->request->query->has('from')) {
                $from = $this->request->query->get('from');
                if (!empty($from)) {
                    $this->helper->preciseFullEmailAddress($from);
                }
                $emailModel->setFrom($from);
            } else {
                $user = $this->helper->getUser();
                if ($user) {
                    $emailModel->setFrom($this->helper->buildFullEmailAddress($user));
                }
            }
        }
    }

    /**
     * @param EmailModel $emailModel
     */
    protected function applyRecipients(EmailModel $emailModel)
    {
        $emailModel->setTo(array_merge($emailModel->getTo(), $this->getRecipients($emailModel, EmailRecipient::TO)));
        $emailModel->setCc(array_merge($emailModel->getCc(), $this->getRecipients($emailModel, EmailRecipient::CC)));
        $emailModel->setBcc(array_merge($emailModel->getBcc(), $this->getRecipients($emailModel, EmailRecipient::BCC)));
    }

    /**
     * @param EmailModel $emailModel
     * @param string $type
     *
     * @return array
     */
    protected function getRecipients(EmailModel $emailModel, $type)
    {
        $addresses = [];
        if ($this->request->query->has($type)) {
            $address = trim($this->request->query->get($type));
            if (!empty($address)) {
                $this->helper->preciseFullEmailAddress(
                    $address,
                    $emailModel->getEntityClass(),
                    $emailModel->getEntityId()
                );
            }
            $addresses = [$address];
        }
        return $addresses;
    }

    /**
     * @param EmailModel $model
     */
    protected function applySubject(EmailModel $model)
    {
        if ($this->request->query->has('subject')) {
            $subject = trim($this->request->query->get('subject'));
            $model->setSubject($subject);
        }
    }

    /**
     * @param EmailModel  $emailModel
     * @param EmailEntity $emailEntity
     */
    protected function applyEntityDataFromEmail(EmailModel $emailModel, EmailEntity $emailEntity)
    {
        $entities = $emailEntity->getActivityTargetEntities();
        foreach ($entities as $entity) {
            if ($entity != $this->helper->getUser()) {
                $emailModel->setEntityClass(ClassUtils::getClass($entity));
                $emailModel->setEntityId($entity->getId());

                return;
            }
        }
    }

    /**
     * @param EmailModel $emailModel
     */
    protected function applySignature(EmailModel $emailModel)
    {
        $signature = $this->configManager->get('oro_email.signature');
        if ($signature) {
            $emailModel->setSignature($signature);
        }
    }
}
