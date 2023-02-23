<?php

namespace Oro\Bundle\ApiBundle\Request;

use Symfony\Component\Validator;

/**
 * Delegates the extraction of information from a validation constraint object to all child extractors.
 */
class ChainConstraintTextExtractor implements ConstraintTextExtractorInterface
{
    /** @var iterable<ConstraintTextExtractorInterface> */
    private iterable $extractors;

    /**
     * @param iterable<ConstraintTextExtractorInterface> $extractors
     */
    public function __construct(iterable $extractors)
    {
        $this->extractors = $extractors;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstraintStatusCode(Validator\Constraint $constraint): ?int
    {
        $result = null;
        foreach ($this->extractors as $extractor) {
            $result = $extractor->getConstraintStatusCode($constraint);
            if (null !== $result) {
                break;
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstraintCode(Validator\Constraint $constraint): ?string
    {
        $result = null;
        foreach ($this->extractors as $extractor) {
            $result = $extractor->getConstraintCode($constraint);
            if (null !== $result) {
                break;
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstraintType(Validator\Constraint $constraint): ?string
    {
        $result = null;
        foreach ($this->extractors as $extractor) {
            $result = $extractor->getConstraintType($constraint);
            if (null !== $result) {
                break;
            }
        }

        return $result;
    }
}
