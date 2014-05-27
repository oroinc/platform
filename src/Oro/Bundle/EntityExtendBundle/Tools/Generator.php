<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Symfony\Component\Yaml\Yaml;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use CG\Generator\PhpProperty;
use CG\Generator\Writer;

use Doctrine\Common\Inflector\Inflector;

class Generator
{
    /** @var string */
    protected $cacheDir;

    /** @var string */
    protected $entityCacheDir;

    /** @var Writer */
    protected $writer = null;

    /** @var array|GeneratorExtension[] */
    protected $extensions = [];

    /** @var array|GeneratorExtension[] */
    protected $supportedExtensions = [];

    /**
     * @param string $cacheDir
     * @param array  $extensions
     */
    public function __construct($cacheDir, $extensions = [])
    {
        $this->cacheDir = $cacheDir;
        $this->entityCacheDir = ExtendClassLoadingUtils::getEntityCacheDir($cacheDir);
        $this->extensions = $extensions;
    }

    /**
     * Generates extended entities
     *
     * @param array $config
     */
    public function generate(array $config)
    {
        // filter supported extensions and pre-process configuration
        foreach ($this->extensions as $extension) {
            if (!$extension->supports($config)) {
                continue;
            }

            $this->supportedExtensions = $extension;
            $extension->preProcessEntityConfiguration($config);
        }

        $aliases = [];
        foreach ($config as $item) {
            // $item can be modified in any extension in preProcessEntityConfiguration method
            $classNameArray = explode('\\', $item['entity']);
            file_put_contents(
                $this->entityCacheDir . '/' . array_pop($classNameArray) . '.orm.yml',
                Yaml::dump($item['doctrine'], 5)
            );

            $this->generateClass($item);

            if ($item['type'] == 'Extend') {
                $aliases[$item['entity']] = $item['parent'];
            }
        }

        // dump aliases
        file_put_contents(
            ExtendClassLoadingUtils::getAliasesPath($this->cacheDir),
            Yaml::dump($aliases)
        );
    }

    /**
     * @param array $item
     */
    protected function generateClass(array $item)
    {
        $this->writer = new Writer();

        $class = PhpClass::create($item['entity']);

        if ($item['type'] == 'Extend') {
            if (isset($item['inherit'])) {
                $class->setParentClassName($item['inherit']);
            }
        } else {
            $class->setProperty(PhpProperty::create('id')->setVisibility('protected'));
            $class->setMethod($this->generateClassMethod('getId', 'return $this->id;'));

            $this->generateToStringMethod($item, $class);
        }

        $class->setInterfaceNames(['Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface']);

        $this->generateProperties('property', $item, $class);
        $this->generateProperties('relation', $item, $class);
        $this->generateProperties('default', $item, $class);
        $this->generateCollectionMethods($item, $class);

        // allow extensions to add
        foreach ($this->supportedExtensions as $extension) {
            $extension->generate($item, $class);
        }

        $classArray = explode('\\', $item['entity']);
        $className  = array_pop($classArray);

        $filePath = $this->entityCacheDir . '/' . $className . '.php';
        $strategy = new DefaultGeneratorStrategy();

        file_put_contents($filePath, "<?php\n\n" . $strategy->generate($class));
    }

    /**
     * TODO: custom entity instance as manyToOne relation find the way to show it on view
     * we should mark some field as title
     *
     * @param array    $config
     * @param PhpClass $class
     */
    protected function generateToStringMethod(array $config, PhpClass $class)
    {
        $toString = [];
        foreach ($config['property'] as $propKey => $propValue) {
            if ($config['doctrine'][$config['entity']]['fields'][$propKey]['type'] == 'string') {
                $toString[] = '$this->get' . ucfirst(Inflector::camelize($propValue)) . '()';
            }
        }

        $toStringBody = 'return (string) $this->getId();';
        if (count($toString) > 0) {
            $toStringBody = 'return (string)' . implode(' . ', $toString) . ';';
        }
        $class->setMethod($this->generateClassMethod('__toString', $toStringBody));
    }

    /**
     * @param string   $propertyType property or relation
     * @param array    $config
     * @param PhpClass $class
     */
    protected function generateProperties($propertyType, array $config, PhpClass $class)
    {
        foreach ($config[$propertyType] as $property => $method) {
            $class
                ->setProperty(PhpProperty::create($property)->setVisibility('protected'))
                ->setMethod(
                    $this->generateClassMethod(
                        'get' . ucfirst(Inflector::camelize($method)),
                        'return $this->' . $property . ';'
                    )
                )
                ->setMethod(
                    $this->generateClassMethod(
                        'set' . ucfirst(Inflector::camelize($method)),
                        '$this->' . $property . ' = $value; return $this;',
                        ['value']
                    )
                );
        }
    }

    /**
     * @param array    $config
     * @param PhpClass $class
     */
    protected function generateCollectionMethods(array $config, PhpClass $class)
    {
        foreach ($config['addremove'] as $addremove => $method) {
            $class
                ->setMethod(
                    $this->generateClassMethod(
                        'add' . ucfirst(Inflector::camelize($method['self'])),
                        'if (!$this->' . $addremove . ') {
                            $this->' . $addremove . ' = new \Doctrine\Common\Collections\ArrayCollection();
                        }
                        if (!$this->' . $addremove . '->contains($value)) {
                            $this->' . $addremove . '->add($value);
                            $value->' . ($method['is_target_addremove'] ? 'add' : 'set')
                        . ucfirst(Inflector::camelize($method['target'])) .'($this);
                        }',
                        array('value')
                    )
                )
                ->setMethod(
                    $this->generateClassMethod(
                        'remove' . ucfirst(Inflector::camelize($method['self'])),
                        'if ($this->' . $addremove . ' && $this->' . $addremove . '->contains($value)) {
                            $this->' . $addremove . '->removeElement($value);
                            $value->'. ($method['is_target_addremove'] ? 'remove' : 'set')
                        . ucfirst(Inflector::camelize($method['target']))
                        .'(' . ($method['is_target_addremove'] ? '$this' : 'null') . ');
                        }',
                        ['value']
                    )
                );
        }
    }

    /**
     * @param string $methodName
     * @param string $methodBody
     * @param array  $methodArgs
     *
     * @return PhpMethod
     */
    protected function generateClassMethod($methodName, $methodBody, $methodArgs = [])
    {
        $this->writer->reset();
        $method = PhpMethod::create($methodName)->setBody(
            $this->writer->write($methodBody)->getContent()
        );

        if (count($methodArgs)) {
            foreach ($methodArgs as $arg) {
                $method->addParameter(PhpParameter::create($arg));
            }
        }

        return $method;
    }
}
