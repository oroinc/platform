<?php

namespace Oro\Bundle\UIBundle\ContentProvider;

use Symfony\Component\HttpFoundation\Request;

class CurrentRouteContentProvider extends AbstractContentProvider
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
        return $this->request->attributes->get('_route');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'currentRoute';
    }
}
