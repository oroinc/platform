<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

use Twig\Source;
use Twig\Template;

class TestTemplate extends Template
{
    /**
     * {@inheritdoc}
     */
    public function getTemplateName(): string
    {
        return 'test';
    }

    /**
     * {@inheritdoc}
     */
    protected function doDisplay(array $context, array $blocks = array()): iterable
    {
        yield 'test';
    }

    /**
     * {@inheritdoc}
     */
    public function getDebugInfo(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceContext(): Source
    {
        return new Source('test', 'test');
    }
}
