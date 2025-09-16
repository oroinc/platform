<?php

namespace Oro\Bundle\PlatformBundle\ArgumentResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

/**
 * Custom argument resolver that extends base Symfony argument resolver functionality.
 *
 * This resolver automatically sets controller arguments as request attributes based on parameter types,
 * allowing for easier access to resolved arguments throughout the request lifecycle.
 */
class ArgumentResolver implements ArgumentResolverInterface
{
    public function __construct(
        private ArgumentResolverInterface $inner
    ) {
    }

    #[\Override]
    public function getArguments(
        Request $request,
        callable $controller,
        ?\ReflectionFunctionAbstract $reflector = null
    ): array {
        $arguments = $this->inner->getArguments($request, $controller, $reflector);
        if (null !== $reflector) {
            $this->setArgumentsAsRequestAttributes($request, $controller, $arguments, $reflector);
        }

        return $arguments;
    }

    /**
     * Sets controller arguments as request attributes based on parameter types
     */
    private function setArgumentsAsRequestAttributes(
        Request $request,
        callable $controller,
        array $arguments,
        \ReflectionFunctionAbstract $reflector
    ): void {
        foreach ($reflector->getParameters() as $param) {
            $type = $param->getType();
            $class = $this->getParamClassByType($type);

            if (null !== $class && $request instanceof $class) {
                continue;
            }

            $name = $param->getName();
            if ($type && !$request->attributes->has($name)) {
                foreach ($arguments as $argument) {
                    if (null !== $class && $argument instanceof $class) {
                        $request->attributes->set($name, $argument);
                    }
                }
            }
        }
    }

    /**
     * Extracts class name from reflection type
     * Handles both single types and union types, returning the first non-builtin type found
     */
    private function getParamClassByType(?\ReflectionType $type): ?string
    {
        if (null === $type) {
            return null;
        }

        foreach ($type instanceof \ReflectionUnionType ? $type->getTypes() : [$type] as $reflectionType) {
            if (!$reflectionType->isBuiltin()) {
                return $reflectionType->getName();
            }
        }

        return null;
    }
}
