<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Delegates the extraction of information from an exception object to all child extractors.
 */
class ChainExceptionTextExtractor implements ExceptionTextExtractorInterface
{
    /** @var iterable<ExceptionTextExtractorInterface> */
    private iterable $extractors;

    /**
     * @param iterable<ExceptionTextExtractorInterface> $extractors
     */
    public function __construct(iterable $extractors)
    {
        $this->extractors = $extractors;
    }

    /**
     * {@inheritDoc}
     */
    public function getExceptionStatusCode(\Exception $exception): ?int
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
     * {@inheritDoc}
     */
    public function getExceptionCode(\Exception $exception): ?string
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
     * {@inheritDoc}
     */
    public function getExceptionType(\Exception $exception): ?string
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
     * {@inheritDoc}
     */
    public function getExceptionText(\Exception $exception): ?string
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
