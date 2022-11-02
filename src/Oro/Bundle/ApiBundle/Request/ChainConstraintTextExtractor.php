<?php

namespace Oro\Bundle\ApiBundle\Request;

use Symfony\Component\Validator;

/**
 * Delegates the extraction of information from a validation constraint object to all child extractors.
 */
class ChainConstraintTextExtractor implements ConstraintTextExtractorInterface
{
    /** @var iterable|ConstraintTextExtractorInterface[] */
    private $extractors;

    /**
     * @param iterable|ConstraintTextExtractorInterface[] $extractors
     */
    public function __construct(iterable $extractors)
    {
        $this->extractors = $extractors;
    }

    /**
     * {@inheritdoc}
     */
    public function getConstraintStatusCode(Validator\Constraint $constraint)
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
     * {@inheritdoc}
     */
    public function getConstraintCode(Validator\Constraint $constraint)
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
     * {@inheritdoc}
     */
    public function getConstraintType(Validator\Constraint $constraint)
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
