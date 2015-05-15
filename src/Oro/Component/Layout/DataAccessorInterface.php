<?php

namespace Oro\Component\Layout;

interface DataAccessorInterface extends \ArrayAccess
{
    /**
     * Returns an unique identifier of data by the name of the data provider.
     * The identifier can be a url, route, some unique key or something else that uniquely identifies the data.
     *
     * Examples:
     * * "/api/rest/products/$context.product_id"
     * * array('route' => 'api_get_product', 'parameters' => array('id' => '$context.product_id'))
     * Please note that in these examples "$context.product_id" means that the id of a product
     * is received from the layout context.
     *
     * @param string $name The name of the data provider
     *
     * @return mixed
     *
     * @throws Exception\InvalidArgumentException if the data provider cannot be loaded
     */
    public function getIdentifier($name);

    /**
     * Returns data by the name of the data provider.
     *
     * @param string $name The name of the data provider
     *
     * @return mixed
     *
     * @throws Exception\InvalidArgumentException if the data provider cannot be loaded
     */
    public function get($name);
}
