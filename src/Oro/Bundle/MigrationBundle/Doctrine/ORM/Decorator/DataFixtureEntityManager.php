<?php

namespace Oro\Bundle\MigrationBundle\Doctrine\ORM\Decorator;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\MigrationBundle\Exception\DataFixtureValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Entity manager decorator that validates entities before or after flush
 */
class DataFixtureEntityManager extends EntityManagerDecorator
{
    /** @var EntityManagerInterface */
    protected $wrapped;
    protected ValidatorInterface $validator;
    protected bool $validateBeforeFlush = true;

    public function __construct(EntityManagerInterface $wrapped, ValidatorInterface $validator)
    {
        parent::__construct($wrapped);
        $this->wrapped = $wrapped;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function flush($entity = null)
    {
        $this->validateBeforeFlush
            ? $this->validateAndFlush($entity)
            : $this->flushAndValidate($entity);
    }

    public function setValidateBeforeFlush(bool $value): void
    {
        $this->validateBeforeFlush = $value;
    }

    protected function validateAndFlush($entity = null): void
    {
        $entitiesToValidate = $this->getEntitiesToValidate();
        $this->validateEntities($entitiesToValidate);
        parent::flush($entity);
    }

    protected function flushAndValidate($entity = null): void
    {
        $entitiesToValidate = $this->getEntitiesToValidate();
        parent::flush($entity);
        // refresh entities to trigger more accurate validation, because many constraints check fully loaded entities
        foreach ($entitiesToValidate as $objectToRefresh) {
            $this->refresh($objectToRefresh);
        }
        $this->validateEntities($entitiesToValidate);
    }

    protected function validateEntities(array $entities): void
    {
        foreach ($entities as $entity) {
            $errors = $this->validator->validate($entity);
            if ($errors->count()) {
                throw new DataFixtureValidationFailedException($errors);
            }
        }
    }

    protected function getEntitiesToValidate(): array
    {
        $unitOfWork = $this->wrapped->getUnitOfWork();

        return array_merge($unitOfWork->getScheduledEntityInsertions(), $unitOfWork->getScheduledEntityUpdates());
    }
}
