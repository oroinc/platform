<?php

namespace Oro\Bundle\TestGeneratorBundle\Generator;

class UnitTestGenerator extends AbstractTestGenerator
{
    /**
     * @param string $className
     */
    public function generate($className)
    {
        $fullTestNameSpace = $this->getNamespaceForTest($className, 'unit');
        $parts = explode('\\', $fullTestNameSpace);
        $testClassName = array_pop($parts);
        $partsOfOriginClass = explode('\\', $className);
        $testedClassName = array_pop($partsOfOriginClass);
        $nameSpace = implode('\\', $parts);
        $testPath = $this->getTestPath($fullTestNameSpace);
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();
        $dependencies = $constructor ? $this->getDependencies($constructor) : [];
        $dependenciesData = $this->getDependenciesData($dependencies);
        $methodsData = $this->getMethodsData($class);
        $this->addClassToUses($className);
        $orderedUses = $this->getOrderedUses($this->usedClasses);
        $content = $this->twig->render(
            '@OroTestGenerator/Tests/unit_template.php.twig',
            [
                'namespace' => $nameSpace,
                'vendors' => $orderedUses,
                'className' => $testClassName,
                'testedClassName' => $testedClassName,
                'testedClassNameVariable' => lcfirst($testedClassName),
                'dependenciesData' => $dependenciesData,
                'methodsData' => $methodsData
            ]
        );
        $this->createFile($testPath, $content);
    }
}
