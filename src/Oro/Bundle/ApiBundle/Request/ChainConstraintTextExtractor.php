<?php

namespace Oro\Bundle\ApiBundle\Request;

use Symfony\Component\Validator;

class ChainConstraintTextExtractor implements ConstraintTextExtractorInterface
{
    /**
     * @var ConstraintTextExtractorInterface[]
     */
    protected $extractors = [];

    /**
     * Registers a given extractor in the chain.
     *
     * @param ConstraintTextExtractorInterface $extractor
     */
    public function addExtractor(ConstraintTextExtractorInterface $extractor)
    {
        $this->extractors[] = $extractor;
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
