<?php

namespace Oro\Bundle\NavigationBundle\ContentProvider;

class TitleContentProvider extends TitleServiceAwareContentProvider
{
    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        try {
            $val = $this->titleService->render(array(), null, null, null, true);
        } catch (\Exception $e) {
            print_r($e);
            throw $e;
        }

        return $val;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'title';
    }
}
