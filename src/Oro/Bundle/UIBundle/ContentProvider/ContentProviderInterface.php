<?php

namespace Oro\Bundle\UIBundle\ContentProvider;

interface ContentProviderInterface
{
    /**
     * Get content provider name.
     *
     * @return string
     */
    public function getName();

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent();

    /**
     * Get content provider state.
     *
     * @return bool
     */
    public function isEnabled();

    /**
     * Set content provider state.
     *
     * @param bool $enabled
     * @return ContentProviderInterface
     */
    public function setEnabled($enabled);
}
