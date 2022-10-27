<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox;

use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRenderer;

class TemplateRendererStub extends TemplateRenderer
{
    /**
     * {@inheritdoc}
     */
    protected function getVariableNotFoundMessage(): ?string
    {
        return 'variable_not_found_message';
    }
}
