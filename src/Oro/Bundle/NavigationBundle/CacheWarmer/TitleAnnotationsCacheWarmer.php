<?php

namespace Oro\Bundle\NavigationBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\NavigationBundle\Title\TitleReader\AnnotationsReader;

class TitleAnnotationsCacheWarmer implements CacheWarmerInterface
{
    /** @var AnnotationsReader */
    private $reader;

    /**
     * @param AnnotationsReader $reader
     */
    public function __construct(AnnotationsReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->reader->getControllerClasses();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }
}
