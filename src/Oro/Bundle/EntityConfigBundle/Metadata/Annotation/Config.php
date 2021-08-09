<?php

namespace Oro\Bundle\EntityConfigBundle\Metadata\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Exception\AnnotationException;

/**
 * The annotation that is used to provide configuration of configurable entity.
 * @Annotation
 * @Target("CLASS")
 */
final class Config
{
    private const MODES = [ConfigModel::MODE_DEFAULT, ConfigModel::MODE_HIDDEN, ConfigModel::MODE_READONLY];

    public string $mode = ConfigModel::MODE_DEFAULT;
    public string $routeName = '';
    public string $routeView = '';
    public string $routeCreate = '';
    public array $defaultValues = [];
    public array $routes = [];

    public function __construct(array $data)
    {
        if (isset($data['mode'])) {
            $this->mode = $data['mode'];
        } elseif (isset($data['value'])) {
            $this->mode = $data['value'];
        }

        if (isset($data['routeName'])) {
            $this->routeName = $data['routeName'];
        }

        if (isset($data['routeView'])) {
            $this->routeView = $data['routeView'];
        }

        if (isset($data['routeCreate'])) {
            $this->routeCreate = $data['routeCreate'];
        }

        if (isset($data['defaultValues'])) {
            $this->defaultValues = $data['defaultValues'];
        }

        if (!\in_array($this->mode, self::MODES, true)) {
            throw new AnnotationException(sprintf(
                'Annotation "Config" give invalid parameter "mode" : "%s"',
                $this->mode
            ));
        }

        $this->collectRoutes($data);
    }

    private function collectRoutes(array $data): void
    {
        foreach ($data as $name => $value) {
            if (str_starts_with($name, 'route') && !property_exists($this, $name)) {
                $routeName = lcfirst(str_replace('route', '', $name));
                if (!\array_key_exists($routeName, $this->routes) && strlen($routeName) > 0) {
                    $this->routes[$routeName] = $value;
                }
            }
        }
    }
}
