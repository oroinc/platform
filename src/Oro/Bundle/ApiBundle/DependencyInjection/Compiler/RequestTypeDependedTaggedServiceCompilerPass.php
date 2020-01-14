<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceWithHandlerCompilerPass;

/**
 * Registers tagged services that depend on the request type.
 */
class RequestTypeDependedTaggedServiceCompilerPass extends PriorityNamedTaggedServiceWithHandlerCompilerPass
{
    use ApiTaggedServiceTrait;

    /**
     * @param string        $serviceId
     * @param string        $tagName
     * @param \Closure|null $attributesHandler function (array $attributes, string $serviceId, string $tagName): array
     * @param bool          $isServiceOptional
     */
    public function __construct(
        string $serviceId,
        string $tagName,
        \Closure $attributesHandler = null,
        bool $isServiceOptional = false
    ) {
        if (null === $attributesHandler) {
            $updatedAttributesHandler = function ($attributes, $serviceId) {
                return [$serviceId, $this->getRequestTypeAttribute($attributes)];
            };
        } else {
            $updatedAttributesHandler = function ($attributes, $serviceId, $tagName) use ($attributesHandler) {
                return array_merge(
                    [$serviceId],
                    $attributesHandler($attributes, $serviceId, $tagName),
                    [$this->getRequestTypeAttribute($attributes)]
                );
            };
        }

        parent::__construct($serviceId, $tagName, $updatedAttributesHandler, $isServiceOptional);
    }
}
