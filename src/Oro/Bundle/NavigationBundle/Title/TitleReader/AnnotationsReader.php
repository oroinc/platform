<?php

namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

use Doctrine\Common\Annotations\Reader;
use Oro\Bundle\NavigationBundle\Annotation\TitleTemplate;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * Reads page titles from TitleTemplate annotations of controllers.
 */
class AnnotationsReader extends PhpArrayConfigProvider implements ReaderInterface
{
    /** @var ControllerClassProvider */
    private $controllerClassProvider;

    /** @var Reader */
    private $reader;

    public function __construct(
        string $cacheFile,
        bool $debug,
        ControllerClassProvider $controllerClassProvider,
        Reader $reader
    ) {
        parent::__construct($cacheFile, $debug);
        $this->controllerClassProvider = $controllerClassProvider;
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle($route)
    {
        $config = $this->doGetConfig();

        return $config[$route] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $config = [];
        $controllers = $this->controllerClassProvider->getControllers();
        foreach ($controllers as $routeName => list($class, $method)) {
            /** @var TitleTemplate|null $annotation */
            $annotation = $this->reader->getMethodAnnotation(
                new \ReflectionMethod($class, $method),
                TitleTemplate::class
            );
            if ($annotation) {
                $config[$routeName] = $annotation->getValue();
            }
        }
        $resourcesContainer->addResource($this->controllerClassProvider->getCacheResource());

        return $config;
    }
}
