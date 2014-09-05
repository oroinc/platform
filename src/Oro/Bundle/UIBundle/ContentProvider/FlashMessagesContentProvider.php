<?php

namespace Oro\Bundle\UIBundle\ContentProvider;

use Symfony\Component\HttpFoundation\Session\Session;

class FlashMessagesContentProvider extends AbstractContentProvider
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Session $session
     */
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'flashMessages';
    }
}
