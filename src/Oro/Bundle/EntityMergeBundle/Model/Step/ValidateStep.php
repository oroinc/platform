<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Exception\ValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates entity data before merge execution.
 *
 * Executes validation constraints on the entity data being merged and throws a
 * ValidationException if any constraint violations are found. This step ensures that
 * the merge operation only proceeds with valid data, preventing inconsistent state.
 */
class ValidateStep implements MergeStepInterface
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Validate data
     *
     * @throws ValidationException
     */
    #[\Override]
    public function run(EntityData $data)
    {
        $constraintViolations = $this->validator->validate($data);

        if ($constraintViolations->count()) {
            throw new ValidationException($constraintViolations);
        }
    }
}
