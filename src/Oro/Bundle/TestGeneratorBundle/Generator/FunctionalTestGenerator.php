<?php

namespace Oro\Bundle\TestGeneratorBundle\Generator;

class FunctionalTestGenerator extends AbstractTestGenerator
{
    /**
     * @param string $className
     */
    public function generate($className)
    {
        $this->usedClasses[] = 'Oro\Bundle\TestFrameworkBundle\Test\WebTestCase';
        $fullTestNameSpace = $this->getNamespaceForTest($className, 'functional');
        $parts = explode('\\', $fullTestNameSpace);
        $testClassName = array_pop($parts);
        $partsOfOriginClass = explode('\\', $className);
        $testedClassName = array_pop($partsOfOriginClass);
        $nameSpace = implode('\\', $parts);
        $testPath = $this->getTestPath($fullTestNameSpace);
        $class = new \ReflectionClass($className);
        $methodsData = $this->getMethodsData($class);
        $orderedUses = $this->getOrderedUses($this->usedClasses);
        $content = $this->twig->render(
            '@OroTestGenerator/Tests/functional_template.php.twig',
            [
                'namespace' => $nameSpace,
                'vendors' => $orderedUses,
                'className' => $testClassName,
                'testedClassName' => $testedClassName,
                'testedClassNameVariable' => lcfirst($testedClassName),
                'methodsData' => $methodsData
            ]
        );
        $this->createFile($testPath, $content);
    }


    /**
     * @param \ReflectionClass $class
     * @return array
     */
    protected function getMethodsData(\ReflectionClass $class)
    {
        $data = [];
        $methods = $this->getPublicMethods($class);
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if ($methodName !== '__construct') {
                $data[] = [
                    'name' => $methodName,
                    'testName' => 'test' . ucfirst($methodName)
                ];
            }
        }

        return $data;
    }
}
