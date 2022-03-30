<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Exception\ExternalFileNotAccessibleException;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that the specified external URL matches the URLs allowed in the system configuration.
 * Creates {@see ExternalFile} model from the specified external URL.
 * Handles a valid {@see ExternalFile} model to {@see File::setExternalFile}.
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

    /**
     * {@inheritdoc}
     *
     * @param CustomizeFormDataContext $context
     */
    public function process(ContextInterface $context): void
    {
        switch ($context->getEvent()) {
            case CustomizeFormDataContext::EVENT_PRE_SUBMIT:
                $this->processPreSubmit($context);
                break;
            case CustomizeFormDataContext::EVENT_POST_VALIDATE:
                $this->processPostValidate($context);
                break;
        }
    }

    private function processPreSubmit(CustomizeFormDataContext $context): void
    {
        $data = $context->getData();
        if (!$data || !array_key_exists(self::EXTERNAL_URL_FIELD_NAME, $data)) {
            return;
        }

        $externalUrl = $data[self::EXTERNAL_URL_FIELD_NAME];
        if (empty($externalUrl)) {
            return;
        }

        if (isset($data[self::CONTENT_FIELD_NAME])) {
            FormUtil::addFormError(
                $context->getForm(),
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

        $data[self::CONTENT_FIELD_NAME] = null;

        $violations = $this->configFileValidator->validateExternalFileUrl($externalUrl);
        if (count($violations)) {
            /** @var ConstraintViolation $violation */
            foreach ($violations as $violation) {
                FormUtil::addFormConstraintViolation(
                    $context->getForm(),
                    $violation->getConstraint(),
                    $violation->getMessage(),
                    self::EXTERNAL_URL_FIELD_NAME
                );
            }

            $context->setData($data);

            return;
        }

        try {
            $data[self::CONTENT_FIELD_NAME] = $this->externalFileFactory->createFromUrl($externalUrl);
        } catch (ExternalFileNotAccessibleException $exception) {
            FormUtil::addNamedFormError(
                $context->getForm(),
                'external URL not accessible',
                $this->translator->trans(
                    'oro.attachment.external_file.invalid_url',
                    ['{{ reason }}' => $exception->getReason(), '{{ value }}' => $externalUrl],
                    'validators'
                ),
                self::EXTERNAL_URL_FIELD_NAME
            );
        }

        $context->setData($data);
    }

    private function processPostValidate(CustomizeFormDataContext $context): void
    {
        $form = $context->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var File $file */
        $file = $context->getData();
        $externalFile = $file->getExternalFile();
        if (!$externalFile instanceof ExternalFile || $file->getParentEntityClass() === null) {
            return;
        }

        $violationList = $this->configFileValidator->validate(
            $externalFile,
            $file->getParentEntityClass(),
            $file->getParentEntityFieldName()
        );

        if ($violationList->count()) {
            /** @var ConstraintViolation $violation */
            foreach ($violationList as $violation) {
                FormUtil::addFormConstraintViolation(
                    $form,
                    $violation->getConstraint(),
                    $violation->getMessage(),
                    self::EXTERNAL_URL_FIELD_NAME
                );
            }
        }
    }
}
