<?php

namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

use Oro\Bundle\NavigationBundle\Attribute\TitleTemplate;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\PhpUtils\Attribute\Reader\AttributeReader;
use ReflectionMethod;

/**
 * Reader for TitleTemplate Attributes
 */
class TitleAttributeReader extends PhpArrayConfigProvider implements ReaderInterface
{
    public function __construct(
        string                                   $cacheFile,
        bool                                     $debug,
        private readonly ControllerClassProvider $controllerClassProvider,
        private readonly AttributeReader         $reader
    ) {
        parent::__construct($cacheFile, $debug);
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
            /** @var TitleTemplate|null $attribute */
            $attribute = $this->reader->getMethodAttribute(
                new ReflectionMethod($class, $method),
                TitleTemplate::class
            );
            if ($attribute) {
                $config[$routeName] = $attribute->getValue();
            }
        }
        $resourcesContainer->addResource($this->controllerClassProvider->getCacheResource());

        return $config;
    }
}
