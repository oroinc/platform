<?php

namespace Oro\Bundle\SecurityBundle\Validator\Constraints;

use Oro\Bundle\SecurityBundle\Util\UriSecurityHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Adds constraint violation if string contains a dangerous protocol.
 */
class NotDangerousProtocolValidator extends ConstraintValidator
{
    /** @var UriSecurityHelper */
    private $uriSecurityHelper;

    public function __construct(UriSecurityHelper $uriSecurityHelper)
    {
        $this->uriSecurityHelper = $uriSecurityHelper;
    }

    /**
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!is_string($value)) {
            return;
        }

        if ($this->uriSecurityHelper->uriHasDangerousProtocol($value)) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameters([
                    '{{ allowed }}' => implode(', ', $this->uriSecurityHelper->getAllowedProtocols()),
                    '{{ protocol }}' => (string) parse_url($value, PHP_URL_SCHEME),
                ])
                ->addViolation();
        }
    }
}
