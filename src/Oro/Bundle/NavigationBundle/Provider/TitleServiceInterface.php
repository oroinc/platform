<?php

namespace Oro\Bundle\NavigationBundle\Provider;

interface TitleServiceInterface
{
    /**
     * Set properties from array
     *
     * @param array $values
     *
     * @return TitleServiceInterface
     */
    public function setData(array $values);

    /**
     * Return rendered translated title
     *
     * @param array $params
     * @param null  $title
     * @param null  $prefix
     * @param null  $suffix
     * @param bool  $isJSON
     * @param bool  $isShort
     *
     * @return string
     */
    public function render(
        $params = [],
        $title = null,
        $prefix = null,
        $suffix = null,
        $isJSON = false,
        $isShort = false
    );

    /**
     * Load title template for current route from title readers
     *
     * @param string      $route
     * @param string|null $menuName
     *
     * @return TitleServiceInterface
     */
    public function loadByRoute($route, $menuName = null);

    /**
     * Return serialized title data
     *
     * @return string
     */
    public function getSerialized();
}
