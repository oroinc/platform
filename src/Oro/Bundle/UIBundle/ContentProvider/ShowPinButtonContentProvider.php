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
            $attributes = $this->request->attributes;
            return $attributes->get('_route') != 'oro_default' && !$attributes->get('exception');
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'showPinButton';
    }
}
