<?php

namespace Oro\Component\Layout;

class BlockTypeHelper implements BlockTypeHelperInterface
{
    /** @var LayoutRegistryInterface */
    protected $registry;

    /** @var array */
    protected $types = [];

    /** @var array */
    protected $names = [];

    /** @var array */
    protected $nameMap = [];

    /**
     * @param LayoutRegistryInterface $registry
     */
    public function __construct(LayoutRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function isInstanceOf($blockType, $targetName)
    {
        $name = $this->ensureInitialized($blockType);

        return isset($this->nameMap[$name][$targetName]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeNames($blockType)
    {
        $name = $this->ensureInitialized($blockType);

        return $this->names[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes($blockType)
    {
        $name = $this->ensureInitialized($blockType);

        return $this->types[$name];
    }

    /**
     * @param string|BlockTypeInterface $blockType The block type name or instance of BlockTypeInterface
     *
     * @return string The name of the given block type
     */
    protected function ensureInitialized($blockType)
    {
        if ($blockType instanceof BlockTypeInterface) {
            $name = $blockType->getName();
            $type = $blockType;
        } else {
            $name = $blockType;
            $type = null;
        }

        if (!isset($this->types[$name])) {
            if (!$type) {
                $type = $this->registry->getType($name);
            }

            $types      = [$type];
            $typeName   = $type->getName();
            $names      = [$typeName];
            $nameMap    = [$typeName => true];
            $parentName = $type->getParent();
            while ($parentName) {
                if (isset($this->types[$parentName])) {
                    // use data from already loaded parent type
                    $types   = array_merge($this->types[$parentName], array_reverse($types));
                    $names   = array_merge($this->names[$parentName], array_reverse($names));
                    $nameMap = array_merge($nameMap, $this->nameMap[$parentName]);
                    break;
                } else {
                    $type     = $this->registry->getType($parentName);
                    $typeName = $type->getName();

                    $types[]            = $type;
                    $names[]            = $typeName;
                    $nameMap[$typeName] = true;

                    $parentName = $type->getParent();
                }
            }

            if (null === $parentName) {
                $types = array_reverse($types);
                $names = array_reverse($names);
            }

            $this->types[$name]   = $types;
            $this->names[$name]   = $names;
            $this->nameMap[$name] = $nameMap;

            // initialise all parent types if them are not initialized yet
            $typeNames = array_keys($nameMap);
            $offset    = 0;
            while (false !== ($typeName = next($typeNames))) {
                if (isset($this->nameMap[$typeName])) {
                    break;
                }

                $offset++;
                $this->types[$typeName]   = array_slice($types, 0, -$offset);
                $this->names[$typeName]   = array_slice($names, 0, -$offset);
                $this->nameMap[$typeName] = array_slice($nameMap, $offset);
            }
        }

        return $name;
    }
}
