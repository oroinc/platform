<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Exception\ValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    public function run(EntityData $data)
    {
        $constraintViolations = $this->validator->validate($data);

        if ($constraintViolations->count()) {
            throw new ValidationException($constraintViolations);
        }
    }
}
