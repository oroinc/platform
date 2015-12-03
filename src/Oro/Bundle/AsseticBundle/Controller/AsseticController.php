<?php
namespace Oro\Bundle\AsseticBundle\Controller;

use Assetic\Cache\CacheInterface;
use Oro\Bundle\AsseticBundle\Factory\OroAssetManager;
use Symfony\Bundle\AsseticBundle\Controller\AsseticController as BaseController;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpKernel\Profiler\Profiler;

class AsseticController extends BaseController
{
    public function __construct(
        Request $request,
        OroAssetManager $am,
        CacheInterface $cache,
        $enableProfiler = false,
        Profiler $profiler = null
    ) {
        $this->request = $request;
        $this->am = $am;
        $this->cache = $cache;
        $this->enableProfiler = (boolean) $enableProfiler;
        $this->profiler = $profiler;
    }
}
