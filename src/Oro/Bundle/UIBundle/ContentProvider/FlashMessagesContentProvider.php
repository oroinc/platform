<?php

namespace Oro\Bundle\UIBundle\ContentProvider;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns all flash messages.
 */
class FlashMessagesContentProvider implements ContentProviderInterface
{
    public function __construct(protected RequestStack $requestStack)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->requestStack->getSession()->getFlashBag()->all();
    }
}
