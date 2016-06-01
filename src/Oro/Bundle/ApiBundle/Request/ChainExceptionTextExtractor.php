<?php

namespace Oro\Bundle\ApiBundle\Request;

class ChainExceptionTextExtractor implements ExceptionTextExtractorInterface
{
    /**
     * @var ExceptionTextExtractorInterface[]
     */
    protected $extractors = [];

    /**
     * Registers a given extractor in the chain.
     *
     * @param ExceptionTextExtractorInterface $extractor
     */
    public function addExtractor(ExceptionTextExtractorInterface $extractor)
    {
        $this->extractors[] = $extractor;
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionStatusCode(\Exception $exception)
    {
        $result = null;
        foreach ($this->extractors as $extractor) {
            $result = $extractor->getExceptionStatusCode($exception);
            if (null !== $result) {
                break;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionCode(\Exception $exception)
    {
        $result = null;
        foreach ($this->extractors as $extractor) {
            $result = $extractor->getExceptionCode($exception);
            if (null !== $result) {
                break;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionType(\Exception $exception)
    {
        $result = null;
        foreach ($this->extractors as $extractor) {
            $result = $extractor->getExceptionType($exception);
            if (null !== $result) {
                break;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionText(\Exception $exception)
    {
        $result = null;
        foreach ($this->extractors as $extractor) {
            $result = $extractor->getExceptionText($exception);
            if (null !== $result) {
                break;
            }
        }

        return $result;
    }
}
