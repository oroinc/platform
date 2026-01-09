<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

use Twig\Source;
use Twig\Template;

class TestTemplate extends Template
{
    #[\Override]
    public function getTemplateName(): string
    {
        return 'test';
    }

    #[\Override]
    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        yield 'test';
    }

    #[\Override]
    public function getDebugInfo(): array
    {
        return [];
    }

    #[\Override]
    public function getSourceContext(): Source
    {
        return new Source('test', 'test');
    }
}
