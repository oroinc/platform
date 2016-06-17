<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Psr\Log\LoggerInterface;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;

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
                    $this->emailProcessor->process(
                        $model,
                        $this->emailProcessor->getEmailOrigin($model->getFrom(), $model->getOrganization())
                    );
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
