<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Type\EmailType;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles the action of sending an email from dialog widget.
 */
class EmailHandler implements LoggerAwareInterface
{
    use RequestHandlerTrait;
    use LoggerAwareTrait;

    private FormFactoryInterface $formFactory;
    private EmailModelSender $emailModelSender;

    public function __construct(
        FormFactoryInterface $formFactory,
        EmailModelSender $emailModelSender,
        LoggerInterface $logger
    ) {
        $this->formFactory = $formFactory;
        $this->emailModelSender = $emailModelSender;
        $this->logger = $logger;
    }

    public function createForm(EmailModel $emailModel, array $options = []): FormInterface
    {
        return $this->formFactory->createNamed('oro_email_email', EmailType::class, $emailModel, $options);
    }

    public function handleRequest(FormInterface $form, Request $request): void
    {
        if (\in_array($request->getMethod(), ['POST', 'PUT'], true) && !$request->request->get('_widgetInit')) {
            $this->submitPostPutRequest($form, $request);
        }
    }

    public function handleFormSubmit(FormInterface $form): bool
    {
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EmailModel $emailModel */
            $emailModel = $form->getData();
            if (!$emailModel instanceof EmailModel) {
                return false;
            }

            $emailModel->setAllowToUpdateEmptyContexts(false);

            try {
                $this->emailModelSender->send($emailModel, $emailModel->getOrigin());

                return true;
            } catch (\Exception $exception) {
                $this->logger->error(
                    'Failed to send email model to {email_addresses}: {message}',
                    [
                        'email_addresses' => implode(', ', $emailModel->getTo()),
                        'message' => $exception->getMessage(),
                        'email_model' => $emailModel,
                        'exception' => $exception,
                    ]
                );
                $form->addError(new FormError($exception->getMessage()));
            }
        }

        return false;
    }
}
