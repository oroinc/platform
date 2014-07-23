<?php

namespace Oro\Bundle\NavigationBundle\ContentProvider;

class TitleShortContentProvider extends TitleServiceAwareContentProvider
{
    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->titleService->render(array(), null, null, null, true, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'titleShort';
    }
}
