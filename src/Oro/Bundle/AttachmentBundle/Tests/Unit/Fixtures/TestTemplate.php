<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

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
}
