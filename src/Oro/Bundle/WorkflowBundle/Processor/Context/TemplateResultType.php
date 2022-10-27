<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

class TemplateResultType implements TransitActionResultTypeInterface
{
    const NAME = 'template_response';

    public function getName(): string
    {
        return self::NAME;
    }

    public function supportsCustomForm(): bool
    {
        return true;
    }
}
