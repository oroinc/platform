<?php

namespace Oro\Bundle\UIBundle\ContentProvider;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Returns all flash messages.
 */
class FlashMessagesContentProvider implements ContentProviderInterface
{
    /** @var Session */
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->session->getFlashBag()->all();
    }
}
