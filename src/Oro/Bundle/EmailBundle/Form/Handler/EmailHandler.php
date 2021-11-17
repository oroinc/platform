<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles the action of sending an email from dialog widget.
 */
class EmailHandler
{
    use RequestHandlerTrait;

    private FormInterface $form;

    private RequestStack $requestStack;

    private EmailModelSender $emailModelSender;

    private LoggerInterface $logger;

    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        EmailModelSender $emailModelSender,
        LoggerInterface $logger
    ) {
        $this->form = $form;
        $this->requestStack = $requestStack;
        $this->emailModelSender = $emailModelSender;
        $this->logger = $logger;
    }

    /**
     * Process form
     *
     * @param  Email $model
     * @return bool True on successful processing, false otherwise
     */
    public function process(Email $emailModel)
    {
        $this->form->setData($emailModel);

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)
            && !$request->request->get('_widgetInit')) {
            $this->submitPostPutRequest($this->form, $request);

            if ($this->form->isValid()) {
                try {
                    $this->emailModelSender->send($emailModel, $emailModel->getOrigin());

                    return true;
                } catch (\Exception $exception) {
                    $this->logger->error(
                        sprintf(
                            'Failed to send email model to %s: %s',
                            implode(', ', $emailModel->getTo()),
                            $exception->getMessage()
                        ),
                        ['exception' => $exception, 'emailModel' => $emailModel]
                    );
                    $this->form->addError(new FormError($exception->getMessage()));
                }
            }
        }

        return false;
    }
}
