<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

class TemplateResultType implements TransitActionResultTypeInterface
{
    const NAME = 'template_response';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return bool
     */
    public function supportsCustomForm(): bool
    {
        return true;
    }
}
