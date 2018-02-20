<?php

namespace Oro\Bundle\ImportExportBundle\Processor;

use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Exception\UnexpectedValueException;

class ProcessorRegistry
{
    const TYPE_EXPORT = 'export';
    const TYPE_EXPORT_TEMPLATE = 'export_template';
    const TYPE_IMPORT = 'import';
    const TYPE_IMPORT_VALIDATION = 'import_validation';

    /**
     * Processor storage format:
     * array(
     *     '<type>' => array(
     *         '<processorAlias>' => <processorObject>
     *     )
     * )
     *
     * @var array
     */
    protected $processors = [];

    /**
     * Processor storage format:
     * array(
     *      '<entityName>' => array(
     *          '<type>' => array(
     *              '<processorAlias>' => <processorObject>
     *          )
     *     )
     * )
     *
     * @var array
     */
    protected $processorsByEntity = [];

    /**
     * @param ProcessorInterface $processor
     * @param string $entityName
     * @param string $type
     * @param string $alias
     * @throws LogicException
     */
    public function registerProcessor(ProcessorInterface $processor, $type, $entityName, $alias)
    {
        if (empty($this->processors[$type])) {
            $this->processors[$type] = [];
        }

        if (empty($this->processorsByEntity[$entityName])) {
            $this->processorsByEntity[$entityName] = [];
        }

        if (empty($this->processorsByEntity[$entityName][$type])) {
            $this->processorsByEntity[$entityName][$type] = [];
        }

        if (!empty($this->processors[$type][$alias])) {
            throw new LogicException(
                sprintf('Processor with type "%s" and alias "%s" already exists', $type, $alias)
            );
        }

        if ($processor instanceof EntityNameAwareInterface) {
            $processor->setEntityName($entityName);
        }

        $this->processors[$type][$alias] = $processor;
        $this->processorsByEntity[$entityName][$type][$alias] = $processor;
    }

    /**
     * @param string $type
     * @param string $entityName
     * @param string $alias
     */
    public function unregisterProcessor($type, $entityName, $alias)
    {
        unset($this->processors[$type][$alias]);
        unset($this->processorsByEntity[$entityName][$type][$alias]);
    }

    /**
     * Checks if processor registered
     *
     * @param string $type
     * @param string $alias
     * @return bool
     */
    public function hasProcessor($type, $alias)
    {
        if (empty($this->processors[$type][$alias])) {
            return false;
        }
        return (null === $alias) ? true : !empty($this->processors[$type][$alias]);
    }

    /**
     * Get processor by type and alias
     *
     * @param string $type
     * @param string $alias
     * @return ProcessorInterface
     * @throws UnexpectedValueException
     */
    public function getProcessor($type, $alias)
    {
        if (!$this->hasProcessor($type, $alias)) {
            throw new UnexpectedValueException(
                sprintf('Processor with type "%s" and alias "%s" is not exist', $type, $alias)
            );
        }

        return $this->processors[$type][$alias];
    }

    /**
     * Get all processors by type and entity name
     *
     * @param string $type
     * @param string $entityName
     * @return ProcessorInterface[]
     */
    public function getProcessorsByEntity($type, $entityName)
    {
        if (empty($this->processorsByEntity[$entityName][$type])) {
            return [];
        }

        return $this->processorsByEntity[$entityName][$type];
    }

    /**
     * Get all processors aliases by type and entity name
     *
     * @param string $type
     * @param string $entityName
     * @return array
     */
    public function getProcessorAliasesByEntity($type, $entityName)
    {
        if (empty($this->processorsByEntity[$entityName][$type])) {
            return [];
        }

        return array_keys($this->processorsByEntity[$entityName][$type]);
    }

    /**
     * Get entity name by processor type and alias
     *
     * @param string $type
     * @param string $alias
     * @return string
     * @throws UnexpectedValueException
     */
    public function getProcessorEntityName($type, $alias)
    {
        foreach ($this->processorsByEntity as $entityName => $processors) {
            if (!empty($processors[$type][$alias])) {
                return $entityName;
            }
        }
        throw new UnexpectedValueException(
            sprintf('Processor with type "%s" and alias "%s" is not exist', $type, $alias)
        );
    }

    /**
     * @param string $type

     * @return ProcessorInterface[]
     */
    public function getProcessorsByType($type)
    {
        if (!empty($this->processors[$type])) {
            return $this->processors[$type];
        }

        return [];
    }
}
