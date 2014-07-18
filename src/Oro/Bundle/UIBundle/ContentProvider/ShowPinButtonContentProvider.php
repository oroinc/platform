<?php

namespace Oro\Bundle\UIBundle\ContentProvider;

use Symfony\Component\HttpFoundation\Request;

class ShowPinButtonContentProvider extends AbstractContentProvider
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        $attributes = $this->request->attributes;
        return $attributes->get('_route') && !$attributes->get('exception');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'showPinButton';
    }
}
