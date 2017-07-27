<?php

namespace Oro\Bundle\ApiBundle\Twig;

use Nelmio\ApiDocBundle\Twig\Extension\MarkdownExtension as BaseMarkdownExtension;

class MarkdownExtension extends BaseMarkdownExtension
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function markdown($text)
    {
        if (!$this->markdownParser) {
            parent::__construct();
        }

        return parent::markdown($text);
    }
}
