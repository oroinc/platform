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
     * @param Request|null $request
     */
    public function setRequest(Request $request = null)
    {
        if (!is_null($request)) {
            $this->request = $request;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if ($this->request) {
            return $this->request->attributes->get('_master_request_route');
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'currentRoute';
    }
}
