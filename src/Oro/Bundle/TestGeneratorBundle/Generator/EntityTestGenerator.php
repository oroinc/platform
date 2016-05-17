<?php

namespace Oro\Bundle\TestGeneratorBundle\Generator;

class EntityTestGenerator extends AbstractTestGenerator
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @param string $className
     */
    public function generate($className)
    {
        $this->className = $className;
        $class = new \ReflectionClass($className);
        $propertiesData = $this->getProperties($class);
        $fullTestNameSpace = $this->getNamespaceForTest($className, 'unit');
        $parts = explode('\\', $fullTestNameSpace);
        $testClassName = array_pop($parts);
        $partsOfOriginClass = explode('\\', $className);
        $testedClassName = array_pop($partsOfOriginClass);
        $nameSpace = implode('\\', $parts);
        $testPath = $this->getTestPath($fullTestNameSpace);
        $constructor = $class->getConstructor();
        $dependencies = $constructor ? $this->getDependencies($constructor) : [];
        $dependenciesData = $this->getDependenciesData($dependencies);
        $this->addClassToUses($className);
        $this->addClassToUses('Oro\Component\Testing\Unit\EntityTestCaseTrait');
        $orderedUses = $this->getOrderedUses($this->usedClasses);
        $content = $this->twig->render(
            '@OroTestGenerator/Tests/entity_template.php.twig',
            [
                'namespace' => $nameSpace,
                'vendors' => $orderedUses,
                'className' => $testClassName,
                'testedClassName' => $testedClassName,
                'testedClassNameVariable' => lcfirst($testedClassName),
                'dependenciesData' => $dependenciesData,
                'propertiesData' => $propertiesData
            ]
        );
        $this->createFile($testPath, $content);
    }

    /**
     * @param \ReflectionClass $class
     * @return array
     */
    protected function getProperties(\ReflectionClass $class)
    {
        $data = [];
        $uses = $this->getUses($class);
        foreach ($class->getProperties() as $property) {
            $type = $this->getPropertyType($property);
            $temp = [];
            $temp['propertyName'] = $property->getName();
            if ($type) {
                if ($type === 'integer' || $type === 'float'
                    || $type === 'string' || $type === 'bool' || $type === 'boolean'
                ) {
                    $temp['type'] = $type;
                    $temp = $this->fillScalarType($type, $temp);
                } elseif (strpos($type, '[]') !== false) {
                    $temp['type'] = str_replace('[]', '', $type);
                    $temp['fullClass'] = $this->getFullClassName($temp['type'], $uses);
                    $temp['collection'] = true;
                    $this->addClassToUses($temp['fullClass']);
                } else {
                    $temp['type'] = $type;
                    $temp['fullClass'] = $this->getFullClassName($type, $uses);
                    $this->addClassToUses($temp['fullClass']);
                }
                $data[] = $temp;
            }
        }

        return $this->groupPropertiesByCollection($data);
    }

    /**
     * @param array $properties
     * @return array
     */
    protected function groupPropertiesByCollection($properties)
    {
        $result['collection'] = [];
        $result['simple'] = [];
        foreach ($properties as $property) {
            if (isset($property['collection'])) {
                unset($property['collection']);
                $result['collection'][] = $property;
            } else {
                $result['simple'][] = $property;
            }
        }

        return $result;
    }

    /**
     * @param \ReflectionProperty $property
     * @return string|false
     */
    protected function getPropertyType(\ReflectionProperty $property)
    {
        $doc = $property->getDocComment();
        preg_match_all('#@(.*?)\n#s', $doc, $annotations);
        $annotations = $annotations[1];
        $resultAnnotation = null;
        foreach ($annotations as $annotation) {
            if (strpos($annotation, 'var ') !== false) {
                $annotation = str_replace('var ', '', $annotation);
                if (strpos($annotation, '$') !== false) {
                    $annotation = explode(' ', $annotation)[0];
                }
                $resultAnnotation = $annotation;
                break;
            }
        }
        if (!$resultAnnotation) {
            return false;
        }
        if (strpos($resultAnnotation, '|') === false) {
            return $resultAnnotation;
        } else {
            $parts = explode('|', $resultAnnotation);
            foreach ($parts as $part) {
                if (strpos($part, '[]') !== false) {
                    return $part;
                }
            }
        }

        return false;
    }

    /**
     * @param \ReflectionClass $class
     * @return array
     */
    protected function getUses(\ReflectionClass $class)
    {
        $result = [];
        $lines = file($class->getFileName());
        $i = 4;
        while (strpos($lines[$i], '/*') === false && strpos($lines[$i], 'class ') === false) {
            if ($lines[$i] !== "\n" && strpos($lines[$i], ' as ') === false) {
                $result[] = str_replace(['use ', ';' . PHP_EOL], '', $lines[$i]);
            }
            $i++;
        }

        return $result;
    }

    /**
     * @param $className
     * @param string[] $fullClassNames
     * @return bool|string
     */
    protected function getFullClassName($className, $fullClassNames)
    {
        if (strpos($className, '\\') === 0) {
            return $className;
        }

        foreach ($fullClassNames as $fullClassName) {
            $parts = explode('\\', $fullClassName);
            if ($className === end($parts)) {
                return $fullClassName;
            }
        }
        $parts = explode('\\', $this->className);
        array_pop($parts);

        return implode('\\', $parts) . '\\' . $className;
    }

    /**
     * @param string $type
     * @param array $temp
     * @return array
     */
    protected function fillScalarType($type, $temp)
    {
        if ($type === 'integer') {
            $temp['value'] = 42;

            return $temp;
        } elseif ($type === 'float') {
            $temp['value'] = 3.1415926;

            return $temp;
        } elseif ($type === 'bool' || $type === 'boolean') {
            $temp['value'] = true;

            return $temp;
        } else {
            $temp['value'] = 'some string';
            $temp['quotes'] = true;

            return $temp;
        }
    }
}
