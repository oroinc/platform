<?php

namespace Oro\Bundle\AttachmentBundle\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\FormBundle\Utils\RegExpUtils;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates the external URL of {@see ExternalFile}.
 */
class ExternalFileUrlValidator extends ConstraintValidator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param ExternalFile|string|null $value ExternalFile model or external URL
     * @param ExternalFileUrl $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ExternalFileUrl) {
            throw new UnexpectedTypeException($constraint, ExternalFileUrl::class);
        }

        if (null === $value) {
            return;
        }

        if ($value instanceof ExternalFile) {
            $value = $value->getUrl();
        }

        if (!is_scalar($value)) {
            throw new UnexpectedValueException($value, 'scalar');
        }

        if (!$constraint->allowedUrlsRegExp) {
            $this->context
                ->buildViolation($constraint->emptyRegExpMessage ?: $constraint->doesNoMatchRegExpMessage)
                ->setCode(ExternalFileUrl::EMPTY_REGEXP_ERROR)
                ->addViolation();
            return;
        }

        $regExpError = RegExpUtils::validateRegExp($constraint->allowedUrlsRegExp);
        if ($regExpError !== null) {
            $this->context
                ->buildViolation($constraint->invalidRegExpMessage)
                ->setParameter('{{ code }}', $this->formatValue(ExternalFileUrl::INVALID_REGEXP_ERROR))
                ->setCode(ExternalFileUrl::INVALID_REGEXP_ERROR)
                ->addViolation();

            $this->logger->error(
                'Allowed URLs regular expression ({regexp}) is invalid: {reason}',
                [
                    'regexp' => $constraint->allowedUrlsRegExp,
                    'reason' => $regExpError,
                    'code' => ExternalFileUrl::INVALID_REGEXP_ERROR,
                ]
            );

            return;
        }

        if (!preg_match($constraint->allowedUrlsRegExp, $value)) {
            $this->context
                ->buildViolation($constraint->doesNoMatchRegExpMessage)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setParameter('{{ regexp }}', $this->formatValue($constraint->allowedUrlsRegExp))
                ->setCode(ExternalFileUrl::INVALID_URL_REGEXP_ERROR)
                ->addViolation();
        }
    }
}
