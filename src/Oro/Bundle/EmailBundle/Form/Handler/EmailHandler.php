<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\Translator;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Entity\Util\EmailUtil;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Mailer\Processor;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;

class EmailHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var Processor
     */
    protected $emailProcessor;

    /**
     * @var EmailAddressManager
     */
    protected $emailAddressManager;

    /**
     * @var NameFormatter
     */
    protected $nameFormatter;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param EntityManager $em
     * @param Translator $translator
     * @param SecurityContextInterface $securityContext
     * @param EmailAddressManager $emailAddressManager
     * @param LoggerInterface $logger
     * @param Processor $emailProcessor
     * @param NameFormatter $nameFormatter
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        EntityManager $em,
        Translator $translator,
        SecurityContextInterface $securityContext,
        EmailAddressManager $emailAddressManager,
        Processor $emailProcessor,
        LoggerInterface $logger,
        NameFormatter $nameFormatter
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->em = $em;
        $this->translator = $translator;
        $this->securityContext = $securityContext;
        $this->emailAddressManager = $emailAddressManager;
        $this->emailProcessor = $emailProcessor;
        $this->logger = $logger;
        $this->nameFormatter = $nameFormatter;
    }

    /**
     * Process form
     *
     * @param  Email $model
     * @return bool True on successful processing, false otherwise
     */
    public function process(Email $model)
    {
        if ($this->request->getMethod() === 'GET') {
            $this->initModel($model);
        }
        $this->form->setData($model);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                try {
                    $this->emailProcessor->process($model);
                    return true;
                } catch (\Exception $ex) {
                    $this->logger->error('Email sending failed.', array('exception' => $ex));
                    $this->form->addError(
                        new FormError($this->translator->trans('oro.email.handler.unable_to_send_email'))
                    );
                }
            }
        }

        return false;
    }

    /**
     * Populate a model with initial data.
     * This method is used to load an initial data from a query string
     *
     * @param Email $model
     */
    protected function initModel(Email $model)
    {
        if ($this->request->query->has('gridName')) {
            $model->setGridName($this->request->query->get('gridName'));
        }
        if ($this->request->query->has('from')) {
            $from = $this->request->query->get('from');
            if (!empty($from)) {
                $this->preciseFullEmailAddress($from);
            }
            $model->setFrom($from);
        } else {
            $user = $this->getUser();
            if ($user) {
                $model->setFrom(
                    EmailUtil::buildFullEmailAddress(
                        $user->getEmail(),
                        $this->nameFormatter->format($user)
                    )
                );
            }
        }
        if ($this->request->query->has('to')) {
            $to = trim($this->request->query->get('to'));
            if (!empty($to)) {
                $this->preciseFullEmailAddress($to);
            }
            $model->setTo(array($to));
        }
        if ($this->request->query->has('subject')) {
            $subject = trim($this->request->query->get('subject'));
            $model->setSubject($subject);
        }
    }

    /**
     * @param string $emailAddress
     * @return string
     */
    protected function preciseFullEmailAddress(&$emailAddress)
    {
        if (!EmailUtil::isFullEmailAddress($emailAddress)) {
            $repo = $this->emailAddressManager->getEmailAddressRepository($this->em);
            $emailAddressObj = $repo->findOneBy(array('email' => $emailAddress));
            if ($emailAddressObj) {
                $owner = $emailAddressObj->getOwner();
                if ($owner) {
                    $emailAddress = EmailUtil::buildFullEmailAddress(
                        $emailAddress,
                        $this->nameFormatter->format($owner)
                    );
                }
            }
        }
    }

    /**
     * Get the current authenticated user
     *
     * @return UserInterface|null
     */
    protected function getUser()
    {
        $token = $this->securityContext->getToken();
        if ($token) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                return $user;
            }
        }

        return null;
    }
}
