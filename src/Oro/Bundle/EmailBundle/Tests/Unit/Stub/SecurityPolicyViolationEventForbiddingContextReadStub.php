<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Stub;

use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyViolationEvent;

/**
 * A security policy violation event whose Twig context must not be read.
 */
class SecurityPolicyViolationEventForbiddingContextReadStub extends EmailTemplateSecurityPolicyViolationEvent
{
    public const string ERROR_MESSAGE = 'The Twig context must not be read: it carries secrets';

    #[\Override]
    public function getContext(): array
    {
        throw new \LogicException(self::ERROR_MESSAGE);
    }
}
