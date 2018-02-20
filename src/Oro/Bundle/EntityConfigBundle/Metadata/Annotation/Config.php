<?php

namespace Oro\Bundle\EntityConfigBundle\Metadata\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Exception\AnnotationException;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Config
{
    /** @var string */
    public $mode = ConfigModel::MODE_DEFAULT;

    /** @var string */
    public $routeName = '';

    /** @var string */
    public $routeView = '';

    /** @var string */
    public $routeCreate = '';

    /** @var array */
    public $defaultValues = array();

    /** @var array */
    public $routes = [];

    /**
     * @param array $data
     */
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

        if (!is_array($this->defaultValues)) {
            throw new AnnotationException(
                sprintf(
                    'Annotation "Config" parameter "defaultValues" expect "array" but "%s" given',
                    gettype($this->defaultValues)
                )
            );
        }

        $availableMode = array(
            ConfigModel::MODE_DEFAULT,
            ConfigModel::MODE_HIDDEN,
            ConfigModel::MODE_READONLY
        );

        if (!in_array($this->mode, $availableMode, true)) {
            throw new AnnotationException(
                sprintf('Annotation "Config" give invalid parameter "mode" : "%s"', $this->mode)
            );
        }

        $this->collectRoutes($data);
    }

    /**
     * @param array $data
     */
    protected function collectRoutes(array $data)
    {
        foreach ($data as $name => $value) {
            if (strpos($name, 'route') !== 0 || property_exists($this, $name)) {
                continue;
            }

            $routeName = lcfirst(str_replace('route', '', $name));

            if (!array_key_exists($routeName, $this->routes) && strlen($routeName) > 0) {
                $this->routes[$routeName] = $value;
            }
        }
    }
}
