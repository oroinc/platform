<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

class EntityCacheWarmer extends CacheWarmer
{
    /**
     * @var ExtendConfigDumper
     */
    private $dumper;

    /**
     * Constructor.
     *
     * @param ExtendConfigDumper $dumper
     */
    public function __construct(ExtendConfigDumper $dumper)
    {
        $this->dumper = $dumper;
    }

    /**
     * {inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->dumper->dump();
    }

    /**
     * {inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }
}
