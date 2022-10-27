<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Delegates the extraction of information from an exception object to all child extractors.
 */
class ChainExceptionTextExtractor implements ExceptionTextExtractorInterface
{
    /** @var iterable|ExceptionTextExtractorInterface[] */
    private $extractors;

    /**
     * @param iterable|ExceptionTextExtractorInterface[] $extractors
     */
    public function __construct(iterable $extractors)
    {
        $this->extractors = $extractors;
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
