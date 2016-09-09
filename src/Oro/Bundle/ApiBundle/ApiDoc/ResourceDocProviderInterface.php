<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

interface ResourceDocProviderInterface
{
    /**
     * Gets a short, human-readable description of API resource.
     *
     * @param string $action
     * @param string $entityDescription
     *
     * @return string|null
     */
    public function getResourceDescription($action, $entityDescription);

    /**
     * Gets a detailed documentation of API resource.
     *
     * @param string $action
     * @param string $entityDescription
     *
     * @return string|null
     */
    public function getResourceDocumentation($action, $entityDescription);

    /**
     * Gets a short, human-readable description of API sub-resource.
     *
     * @param string $action
     * @param string $associationDescription
     * @param string $isCollection
     *
     * @return string|null
     */
    public function getSubresourceDescription($action, $associationDescription, $isCollection);

    /**
     * Gets a detailed documentation of API sub-resource.
     *
     * @param string $action
     * @param string $associationDescription
     * @param string $isCollection
     *
     * @return string|null
     */
    public function getSubresourceDocumentation($action, $associationDescription, $isCollection);
}
