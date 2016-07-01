<?php
namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;

interface ResourceDocProviderInterface
{
    /**
     * Gets a description of an identifier field.
     *
     * @param RequestType $requestType
     *
     * @return string
     */
    public function getIdentifierDescription(RequestType $requestType);

    /**
     * Gets a description of API resource.
     *
     * @param string      $action
     * @param string      $version
     * @param RequestType $requestType
     * @param array       $config
     * @param string|null $entityClass
     *
     * @return string|null
     */
    public function getResourceDescription(
        $action,
        $version,
        RequestType $requestType,
        array $config,
        $entityClass = null
    );

    /**
     * Gets a detailed documentation of API resource.
     *
     * @param string      $action
     * @param string      $version
     * @param RequestType $requestType
     * @param array       $config
     * @param string|null $entityClass
     *
     * @return string|null
     */
    public function getResourceDocumentation(
        $action,
        $version,
        RequestType $requestType,
        array $config,
        $entityClass = null
    );

    /**
     * Gets a description of API sub-resource.
     *
     * @param string      $action
     * @param string      $version
     * @param RequestType $requestType
     * @param array       $config
     * @param string      $entityClass
     * @param string      $associationName
     *
     * @return string|null
     */
    public function getSubresourceDescription(
        $action,
        $version,
        RequestType $requestType,
        array $config,
        $entityClass,
        $associationName
    );

    /**
     * Gets a detailed documentation of API sub-resource.
     *
     * @param string      $action
     * @param string      $version
     * @param RequestType $requestType
     * @param array       $config
     * @param string      $entityClass
     * @param string      $associationName
     *
     * @return string|null
     */
    public function getSubresourceDocumentation(
        $action,
        $version,
        RequestType $requestType,
        array $config,
        $entityClass,
        $associationName
    );
}
