<?php
declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\Command\Util;

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\ContextFactory;

/**
 * Extracts method information about php methods (Method description, arguments and return type and description).
 */
class MethodPhpDocExtractor
{
    private DocBlockFactory $docBlockFactory;
    private ContextFactory $contextFactory;

    public function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
        $this->contextFactory = new ContextFactory();
    }

    public function extractPublicMethodsInfo($object): array
    {
        $ro = new \ReflectionObject($object);
        $reflectionMethods = $ro->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methods = [];
        foreach ($reflectionMethods as $reflectionMethod) {
            if (!preg_match('/^(get|has|is)(.+)$/i', $reflectionMethod->getName())) {
                continue;
            }

            $methods[] = $this->extractMethodInfo($reflectionMethod);
        }

        return $methods;
    }

    private function extractMethodInfo(\ReflectionMethod $reflectionMethod): array
    {
        $methodInfo = [
            'name' => $reflectionMethod->getName(),
            'return' => [
                'type' => $reflectionMethod->getReturnType(),
                'description' => '',
            ],
            'arguments' => [],
        ];

        $parameters = $reflectionMethod->getParameters();
        foreach ($parameters as $parameter) {
            $parameterName = $parameter->getName();
            $methodInfo['arguments'][$parameterName] = [
                'name' => $parameterName,
                'type' => (string)$parameter->getType(),
                'required' => !$parameter->isOptional(),
                'description' => '',
            ];
            if ($parameter->isOptional()) {
                $methodInfo['arguments'][$parameterName]['default'] = $parameter->getDefaultValue();
            }
        }

        $docBlock = $this->docBlockFactory->create(
            $reflectionMethod,
            $this->contextFactory->createFromReflector($reflectionMethod)
        );
        $methodInfo['description'] = trim($docBlock->getSummary());
        $description = \trim((string)$docBlock->getDescription());
        if (!empty($description)) {
            $methodInfo['description'] .= "\n".$description;
        }
        $docBlockParameters = $docBlock->getTagsByName('param');
        /** @var \phpDocumentor\Reflection\DocBlock\Tags\Param $docBlockParameter */
        foreach ($docBlockParameters as $docBlockParameter) {
            $variableName = $docBlockParameter->getVariableName();
            $methodInfo['arguments'][$variableName]['type'] = (string)$docBlockParameter->getType();
            $methodInfo['arguments'][$variableName]['description'] = (string)$docBlockParameter->getDescription();
        }
        $docBlocksReturn = $docBlock->getTagsByName('return');
        /** @var \phpDocumentor\Reflection\DocBlock\Tags\Return_ $docBlockReturn */
        $docBlockReturn = empty($docBlocksReturn) ? null : $docBlocksReturn[0];
        if (null !== $docBlockReturn) {
            $methodInfo['return']['type'] = (string)$docBlockReturn->getType();
            $methodInfo['return']['description'] = (string)$docBlockReturn->getDescription();
        }

        return $methodInfo;
    }
}
