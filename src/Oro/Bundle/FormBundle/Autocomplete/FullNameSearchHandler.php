<?php

namespace Oro\Bundle\FormBundle\Autocomplete;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;

class FullNameSearchHandler extends SearchHandler
{
    /**
     * @var EntityNameResolver
     */
    protected $entityNameResolver;

    /**
     * @param EntityNameResolver $entityNameResolver
     */
    public function setEntityNameResolver(EntityNameResolver $entityNameResolver)
    {
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        $result = parent::convertItem($item);
        $result['fullName'] = $this->getFullName($item);

        return $result;
    }

    /**
     * Apply name formatter to get entity's full name
     *
     * @param mixed $entity
     * @return string
     * @throws \RuntimeException
     */
    protected function getFullName($entity)
    {
        if (!$this->entityNameResolver) {
            throw new \RuntimeException('Name resolver must be configured');
        }

        return $this->entityNameResolver->getName($entity);
    }

    /**
     * Gets key of full name property in result item
     *
     * @return string
     */
    protected function getFullNameKey()
    {
        return 'fullName';
    }
}
