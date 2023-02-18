<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Provides an interface for classes that extracts information from an exception object.
 */
interface ExceptionTextExtractorInterface
{
    /**
     * Returns the HTTP status code applicable to a given Exception object.
     */
    public function getExceptionStatusCode(\Exception $exception): ?int;

    /**
     * Returns an application-specific error code for a given Exception object.
     */
    public function getExceptionCode(\Exception $exception): ?string;

    /**
     * Returns a human-readable representation of the type of a given Exception object.
     */
    public function getExceptionType(\Exception $exception): ?string;

    /**
     * Returns a detailed human-readable representation of a given Exception object.
     */
    public function getExceptionText(\Exception $exception): ?string;
}
