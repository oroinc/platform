<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\AttachmentBundle\Exception\ExternalFileNotAccessibleException;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that the specified external URL matches the URLs allowed in the system configuration.
 * Creates {@see \Oro\Bundle\AttachmentBundle\Model\ExternalFile} model from the specified external URL.
 */
class HandleExternalUrl implements ProcessorInterface
{
    private const EXTERNAL_URL_FIELD_NAME = 'externalUrl';
    private const CONTENT_FIELD_NAME = 'content';

    private ExternalFileFactory $externalFileFactory;
    private ConfigFileValidator $configFileValidator;
    private TranslatorInterface $translator;

    public function __construct(
        ExternalFileFactory $externalFileFactory,
        ConfigFileValidator $configFileValidator,
        TranslatorInterface $translator,
    ) {
        $this->externalFileFactory = $externalFileFactory;
        $this->configFileValidator = $configFileValidator;
        $this->translator = $translator;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */
        $this->processPreSubmit($context);
    }

    private function processPreSubmit(CustomizeFormDataContext $context): void
    {
        $data = $context->getData();
        if (!$data) {
            return;
        }

        $externalUrl = $data[self::EXTERNAL_URL_FIELD_NAME] ?? null;
        if (!$externalUrl) {
            return;
        }

        $form = $context->getForm();
        if (isset($data[self::CONTENT_FIELD_NAME])) {
            FormUtil::addNamedFormError(
                $form,
                Constraint::FORM,
                sprintf(
                    'Either "%s" or "%s" must be specified, but not both',
                    self::EXTERNAL_URL_FIELD_NAME,
                    self::CONTENT_FIELD_NAME
                )
            );
            $data[self::CONTENT_FIELD_NAME] = null;
            $context->setData($data);

            return;
        }

        $violations = $this->configFileValidator->validateExternalFileUrl($externalUrl);
        if ($violations->count()) {
            $this->addExternalUrlFieldValidationErrors($form, $violations);

            return;
        }

        try {
            $data[self::CONTENT_FIELD_NAME] = $this->externalFileFactory->createFromUrl($externalUrl);
        } catch (ExternalFileNotAccessibleException $exception) {
            FormUtil::addNamedFormError(
                $form,
                'external file url constraint',
                $this->translator->trans(
                    'oro.attachment.external_file.invalid_url',
                    ['{{ reason }}' => $exception->getReason()],
                    'validators'
                ),
                self::EXTERNAL_URL_FIELD_NAME
            );
        }
        $context->setData($data);
    }

    private function addExternalUrlFieldValidationErrors(
        FormInterface $form,
        ConstraintViolationListInterface $violations
    ): void {
        /** @var ConstraintViolation $violation */
        foreach ($violations as $violation) {
            FormUtil::addFormConstraintViolation(
                $form,
                $violation->getConstraint(),
                $violation->getMessage(),
                self::EXTERNAL_URL_FIELD_NAME
            );
        }
    }
}
