<?php

namespace Oro\Component\Layout;

interface DataProviderInterface
{
    /**
     * Returns an unique identifier of tied data.
     * The identifier can be a url, route, some unique key or something else that uniquely identifies the data.
     *
     * Examples:
     * * "/api/rest/products/$context.product_id"
     * * array('route' => 'api_get_product', 'parameters' => array('id' => '$context.product_id'))
     * Please note that in these examples "$context.product_id" means that the id of a product
     * is received from the layout context.
     *
     * @return mixed
     */
    public function getIdentifier();

    /**
     * Returns tied data.
     *
     * @param ContextInterface $context The layout context
     *
     * @return mixed
     */
    public function getData(ContextInterface $context);
}
