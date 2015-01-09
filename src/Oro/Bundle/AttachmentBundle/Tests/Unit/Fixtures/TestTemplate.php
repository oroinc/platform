<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

class TestTemplate extends \Twig_Template
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
        echo "test";
    }
}
