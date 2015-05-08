<?php

namespace Oro\Component\Layout;

interface DataProviderInterface
{
    /**
     * Returns an unique identifier of tied data.
     *
     * @return string
     */
    public function getIdentifier();

    /**
     * Returns tied data.
     *
     * @return mixed
     */
    public function getData();
}
