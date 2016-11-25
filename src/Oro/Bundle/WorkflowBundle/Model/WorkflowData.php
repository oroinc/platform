<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Component\Action\Model\AbstractStorage;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class WorkflowData extends AbstractStorage
{
    /**
     * @var array
     */
    protected $mapping;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->mapping = [];
    }

    /**
     * Set mapping between fields.
     *
     * @param array $mapping
     * @return WorkflowData
     */
    public function setFieldsMapping(array $mapping)
    {
        $this->mapping = $mapping;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMappedPath($propertyPath)
    {
        if ($propertyPath instanceof PropertyPathInterface) {
            return $propertyPath;
        }

        if (is_array($this->mapping) && array_key_exists($propertyPath, $this->mapping)) {
            $propertyPath = $this->mapping[$propertyPath];
        }

        return $this->getConstructedPropertyPath($propertyPath);
    }
}
