<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

use Twig\Source;
use Twig\Template;

class TestTemplate extends Template
{
    #[\Override]
    public function getTemplateName()
    {
        return 'test';
    }

    #[\Override]
    protected function doDisplay(array $context, array $blocks = array())
    {
        yield 'test';
    }

    #[\Override]
    public function getDebugInfo()
    {
        return [];
    }

    #[\Override]
    public function getSourceContext()
    {
        return new Source('test', 'test');
    }
}
