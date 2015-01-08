<?php

namespace Oro\Bundle\UIBundle\Provider;

use Symfony\Component\HttpFoundation\Request;

class WidgetContextProvider
{
    /** @var string|bool */
    protected $wid = false;

    /**
     * Returns if working in scope of widget
     *
     * @return bool
     */
    public function isActive()
    {
        return false !== $this->wid;
    }

    /**
     * Returns widget identifier
     *
     * @return bool|string
     */
    public function getWid()
    {
        return $this->wid;
    }

    /**
     * @param Request|null $request
     */
    public function setRequest(Request $request = null)
    {
        if (!is_null($request)) {
            $this->wid = $request->get('_wid', false);
        } else {
            $this->wid = false;
        }
    }
}
