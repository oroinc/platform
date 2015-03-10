<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

interface VoterInterface
{
    /**
     * Vote that should the resource be accepted.
     *
     * @param array  $path
     * @param string $resource
     *
     * @return bool|null Return TRUE for resources that satisfies requirements, FALSE otherwise.
     *                   Should return NULL to let other voters do the job.
     */
    public function vote(array $path, $resource);
}
