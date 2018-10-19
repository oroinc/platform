<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailHandler
{
    use RequestHandlerTrait;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var RequestStack
     */
    protected $requestStack;

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
     * @param RequestStack    $requestStack
     * @param Processor       $emailProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        Processor $emailProcessor,
        LoggerInterface $logger
    ) {
        $this->form                = $form;
        $this->requestStack        = $requestStack;
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

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($this->form, $request);

            if ($this->form->isValid()) {
                try {
                    $this->emailProcessor->process($model, $model->getOrigin());

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
