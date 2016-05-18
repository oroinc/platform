<?php

namespace Oro\Bundle\NavigationBundle\ContentProvider;

class TitleContentProvider extends TitleServiceAwareContentProvider
{
    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->titleService->render(array(), null, null, null, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'title';
    }
}
