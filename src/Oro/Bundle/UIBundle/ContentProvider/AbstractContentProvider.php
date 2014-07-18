<?php

namespace Oro\Bundle\UIBundle\ContentProvider;

abstract class AbstractContentProvider implements ContentProviderInterface
{
    /**
     * @var bool
     */
    protected $enabled;

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
}
