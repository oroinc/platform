<?php

namespace Oro\Bundle\NavigationBundle\ContentProvider;

class TitleSerializedContentProvider extends TitleServiceAwareContentProvider
{
    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->titleService->getSerialized();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'titleSerialized';
    }
}
