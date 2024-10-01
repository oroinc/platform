<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

class TemplateResultType implements TransitActionResultTypeInterface
{
    const NAME = 'template_response';

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function supportsCustomForm(): bool
    {
        return true;
    }
}
