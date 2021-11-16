<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

use Twig\Source;
use Twig\Template;

class TestTemplate extends Template
{
    /**
     * {@inheritdoc}
     */
    public function getTemplateName()
    {
        return 'test';
    }

    /**
     * {@inheritdoc}
     */
    protected function doDisplay(array $context, array $blocks = array())
    {
        echo 'test';
    }

    /**
     * {@inheritdoc}
     */
    public function getDebugInfo()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceContext()
    {
        return new Source('test', 'test');
    }
}
