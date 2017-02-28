<?php

namespace Oro\Bundle\NavigationBundle\Provider;

interface TitleServiceInterface
{
    /**
     * Set properties from array
     *
     * @param array $values
     * @return $this
     */
    public function setData(array $values);

    /**
     * Return rendered translated title
     *
     * @param array $params
     * @param null  $title
     * @param null  $prefix
     * @param null  $suffix
     * @return string
     */
    public function render($params = [], $title = null, $prefix = null, $suffix = null);

    /**
     * Load title template for current route from title readers
     *
     * @param string $route
     *
     * @return TitleServiceInterface
     */
    public function loadByRoute($route);

    /**
     * Return serialized title data
     *
     * @return string
     */
    public function getSerialized();

    /**
     * Create full title based on short title and route name
     *
     * @param string $route
     * @param string $title
     *
     * @return string
     */
    public function createTitle($route, $title);
}
