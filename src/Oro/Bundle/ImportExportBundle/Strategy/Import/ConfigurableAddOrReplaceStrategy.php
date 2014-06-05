<?php

namespace Oro\Bundle\ImportExportBundle\Strategy\Import;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;

class ConfigurableAddOrReplaceStrategy implements StrategyInterface, ContextAwareInterface, EntityNameAwareInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var ImportStrategyHelper
     */
    protected $helper;

    /**
     * @param ImportStrategyHelper $helper
     */
    public function __construct(ImportStrategyHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $this->assertEnvironment($entity);

        $entity = $this->processEntity($entity, true);
        $entity = $this->validateAndUpdateContext($entity);

        return $entity;
    }

    protected function processEntity($entity, $isFullData = false)
    {
        $entityManager = $this->helper->getEntityManager($this->entityName);

        $identifier = $this->getEntityIdentifier($entity);
        $identifierName = current(array_keys($identifier));
        $identifierValue = current($identifier);

        $existingEntity = null;
        if (!empty($identifierValue)) {
            $existingEntity = $entityManager->find($this->entityName, $identifierValue);
        }

        // TODO: Implement processing

        if ($existingEntity) {
            $entity = $existingEntity;
        } else {
            $this->resetIdentifier($entity);
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @return null|object
     */
    protected function validateAndUpdateContext($entity)
    {
        // validate entity
        $validationErrors = $this->helper->validateEntity($entity);
        if ($validationErrors) {
            $this->context->incrementErrorEntriesCount();
            $this->helper->addValidationErrors($validationErrors, $this->context);
            return null;
        }

        // increment context counter
        $identifier = $this->getEntityIdentifier($entity);
        if ($identifier && current($identifier)) {
            $this->context->incrementReplaceCount();
        } else {
            $this->context->incrementAddCount();
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @return array
     */
    protected function getEntityIdentifier($entity)
    {
        $entityManager = $this->helper->getEntityManager($this->entityName);
        return $entityManager->getClassMetadata($this->entityName)->getIdentifierValues($entity);
    }

    /**
     * @param object $entity
     */
    protected function assertEnvironment($entity)
    {
        if (!$this->context) {
            throw new LogicException('Strategy must have import/export context');
        }

        if (!$this->entityName) {
            throw new LogicException('Strategy must know about entity name');
        }

        $entityClass = $this->entityName;
        if (!$entity instanceof $entityClass) {
            throw new InvalidArgumentException(sprintf('Imported entity must be instance of %s', $entityClass));
        }
    }

    /**
     * @param object $entity
     */
    protected function resetIdentifier($entity)
    {
        $identifierName = current(array_keys($this->getEntityIdentifier($entity)));

        $reflection = new \ReflectionProperty(ClassUtils::getClass($entity), $identifierName);
        $reflection->setAccessible(true);
        $reflection->setValue($entity, null);
    }
}
