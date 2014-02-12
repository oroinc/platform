<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

use Symfony\Component\Validator\ValidatorInterface;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class ValidateStep implements MergeStepInterface
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Validate data
     *
     * @param EntityData $data
     * @throws InvalidArgumentException
     */
    public function run(EntityData $data)
    {
        if (count($data->getEntities()) < 2) {
            // @todo Add rule to validation.yml
            throw new InvalidArgumentException('Cannot merge less than 2 entities.');
        }

        if (!$data->getMasterEntity()) {
            // @todo Add rule to validation.yml
            throw new InvalidArgumentException('Master entity must be set.');
        }
    }
}
