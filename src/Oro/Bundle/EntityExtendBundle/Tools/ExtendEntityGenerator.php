<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Symfony\Component\Yaml\Yaml;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;
use CG\Generator\PhpProperty;

use Doctrine\Common\Inflector\Inflector;

class ExtendEntityGenerator
{
    /** @var string */
    protected $cacheDir;

    /** @var string */
    protected $entityCacheDir;

    /** @var array|ExtendEntityGeneratorExtension[] */
    protected $extensions = [];

    /** @var array|ExtendEntityGeneratorExtension[]|null */
    protected $sortedExtensions = [];

    /** @var ClassBuilder */
    protected $classBuilder;

    /**
     * @param string $cacheDir
     */
    public function __construct($cacheDir)
    {
        $this->cacheDir       = $cacheDir;
        $this->entityCacheDir = ExtendClassLoadingUtils::getEntityCacheDir($cacheDir);
        $this->classBuilder   = new ClassBuilder();
    }

    /**
     * @param ExtendEntityGeneratorExtension $extension
     * @param int                            $priority
     */
    public function addExtension(ExtendEntityGeneratorExtension $extension, $priority = 0)
    {
        if (!isset($this->extensions[$priority])) {
            $this->extensions[$priority] = [];
        }

        $this->extensions[$priority][] = $extension;
    }

    /**
     * Return sorted extensions
     *
     * @return array|ExtendEntityGeneratorExtension[]
     */
    protected function getExtensions()
    {
        if (empty($this->sortedExtensions) && !empty($this->extensions)) {
            krsort($this->extensions);
            $this->sortedExtensions = call_user_func_array('array_merge', $this->extensions);
        }

        return $this->sortedExtensions;
    }

    /**
     * Generates extended entities
     *
     * @param array $config
     */
    public function generate(array $config)
    {
        // filter supported extensions and pre-process configuration
        foreach ($this->getExtensions() as $extension) {
            if (!$extension->supports(ExtendEntityGeneratorExtension::ACTION_PRE_PROCESS, $config)) {
                continue;
            }

            $extension->preProcessEntityConfiguration($config);
        }

        $aliases = [];
        foreach ($config as $item) {
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
        $class = PhpClass::create($item['entity']);

        if ($item['type'] == 'Extend') {
            if (isset($item['inherit'])) {
                $class->setParentClassName($item['inherit']);
            }
        } else {
            $class->setProperty(PhpProperty::create('id')->setVisibility('protected'));
            $class->setMethod($this->classBuilder->generateClassMethod('getId', 'return $this->id;'));

            $this->generateToStringMethod($item, $class);
        }

        $class->setInterfaceNames(['Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface']);

        $this->generateProperties('property', $item, $class);
        $this->generateProperties('relation', $item, $class);
        $this->generateProperties('default', $item, $class);
        $this->generateCollectionMethods($item, $class);

        foreach ($this->getExtensions() as $extension) {
            if (!$extension->supports(ExtendEntityGeneratorExtension::ACTION_GENERATE, $item)) {
                continue;
            }

            $extension->generate($item, $class);
        }

        // write php class
        $classArray = explode('\\', $item['entity']);
        $className  = array_pop($classArray);
        $filePath   = $this->entityCacheDir . '/' . $className . '.php';

        $strategy   = new DefaultGeneratorStrategy();
        file_put_contents($filePath, "<?php\n\n" . $strategy->generate($class));

        // write yaml metadata, $item can be modified in any extension
        // in preProcessEntityConfiguration or generate methods
        file_put_contents(
            $this->entityCacheDir . DIRECTORY_SEPARATOR . $className . '.orm.yml',
            Yaml::dump($item['doctrine'], 5)
        );
    }

    /**
     * TODO: custom entity instance as manyToOne relation find the way to show it on view
     * we should mark some field as title
     *
     * @param array    $config entity config
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
        $class->setMethod($this->classBuilder->generateClassMethod('__toString', $toStringBody));
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
                    $this->classBuilder->generateClassMethod(
                        'get' . ucfirst(Inflector::camelize($method)),
                        'return $this->' . $property . ';'
                    )
                )
                ->setMethod(
                    $this->classBuilder->generateClassMethod(
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
                    $this->classBuilder->generateClassMethod(
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
                    $this->classBuilder->generateClassMethod(
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

}
