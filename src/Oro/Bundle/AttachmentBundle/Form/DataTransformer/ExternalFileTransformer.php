<?php

namespace Oro\Bundle\AttachmentBundle\Form\DataTransformer;

use Oro\Bundle\AttachmentBundle\Exception\ExternalFileNotAccessibleException;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Transforms a URL to {@see ExternalFile}.
 */
class ExternalFileTransformer implements DataTransformerInterface
{
    private ConfigFileValidator $configFileValidator;

    private ExternalFileFactory $externalFileFactory;

    private ?ExternalFile $originalExternalFile = null;

    public function __construct(
        ConfigFileValidator $configFileValidator,
        ExternalFileFactory $externalFileFactory
    ) {
        $this->configFileValidator = $configFileValidator;
        $this->externalFileFactory = $externalFileFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @param ExternalFile|null $externalFile
     *
     * @return ExternalFile|null
     */
    public function transform($externalFile): ?ExternalFile
    {
        $this->originalExternalFile = $externalFile;

        return $externalFile;
    }

    /**
     * @param string|null $externalUrl
     *
     * @return ExternalFile|null
     *
     * @throws TransformationFailedException When there are constraint violations.
     */
    public function reverseTransform($externalUrl): ?ExternalFile
    {
        if (!$externalUrl) {
            return null;
        }

        if ($externalUrl === $this->originalExternalFile?->getUrl()) {
            return $this->originalExternalFile;
        }

        $violations = $this->configFileValidator->validateExternalFileUrl($externalUrl);
        if (count($violations)) {
            /** @var ConstraintViolation $violation */
            $violation = $violations[0];
            throw new TransformationFailedException(
                $violation->getMessage(),
                0,
                null,
                $violation->getMessageTemplate(),
                $violation->getParameters()
            );
        }

        try {
            $externalFile = $this->externalFileFactory->createFromUrl($externalUrl);
        } catch (ExternalFileNotAccessibleException $exception) {
            throw new TransformationFailedException(
                $exception->getMessage(),
                0,
                $exception,
                'oro.attachment.external_file.invalid_url',
                ['{{ reason }}' => $exception->getReason()]
            );
        }

        return $externalFile;
    }
}
