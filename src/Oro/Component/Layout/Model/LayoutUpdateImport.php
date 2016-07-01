<?php

namespace Oro\Component\Layout\Model;

use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;

class LayoutUpdateImport
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $root;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @param string $id
     * @param string $root
     * @param string $namespace
     */
    public function __construct($id, $root, $namespace)
    {
        $this->id = $id;
        $this->root = $root;
        $this->namespace = $namespace;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param array $importProperties
     * @return static
     */
    public static function createFromArray(array $importProperties)
    {
        if (!array_key_exists(ImportsAwareLayoutUpdateInterface::ID_KEY, $importProperties)) {
            throw new LogicException(sprintf(
                'Import id should be provided, array with "%s" keys given',
                implode(', ', array_keys($importProperties))
            ));
        }
        $importProperties = array_merge([
            ImportsAwareLayoutUpdateInterface::ROOT_KEY => null,
            ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => null,
        ], $importProperties);
        return new static(
            $importProperties[ImportsAwareLayoutUpdateInterface::ID_KEY],
            $importProperties[ImportsAwareLayoutUpdateInterface::ROOT_KEY],
            $importProperties[ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY]
        );
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            ImportsAwareLayoutUpdateInterface::ID_KEY => $this->getId(),
            ImportsAwareLayoutUpdateInterface::ROOT_KEY => $this->getRoot(),
            ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => $this->getNamespace(),
        ];
    }
}
