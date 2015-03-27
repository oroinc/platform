<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Doctrine\ORM\EntityManager;

use Psr\Log\LoggerInterface;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\Translation\Translator;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Class EmailHandler
 *
 * @package Oro\Bundle\EmailBundle\Form\Handler
 */
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
     * @var Processor
     */
    protected $emailProcessor;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param FormInterface   $form
     * @param Request         $request
     * @param Processor       $emailProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        Processor $emailProcessor,
        LoggerInterface $logger
    ) {
        $this->form                = $form;
        $this->request             = $request;
        $this->emailProcessor      = $emailProcessor;
        $this->logger              = $logger;
    }

    /**
     * Process form
     *
     * @param  Email $model
     * @return bool True on successful processing, false otherwise
     */
    public function process(Email $model)
    {
        $this->form->setData($model);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->form->submit($this->request);

             if ($this->form->isValid()) {
                try {
                    $this->emailProcessor->process($model);
                    return true;
                } catch (\Exception $ex) {
                    $this->logger->error('Email sending failed.', ['exception' => $ex]);
                    $this->form->addError(new FormError($ex->getMessage()));
                }
            }
        }

        return false;
    }
}
