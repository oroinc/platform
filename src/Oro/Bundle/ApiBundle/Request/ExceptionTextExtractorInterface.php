<?php

namespace Oro\Bundle\ApiBundle\Request;

interface ExceptionTextExtractorInterface
{
    /**
     * Returns the HTTP status code applicable to a given Exception object.
     *
     * @param \Exception $exception
     *
     * @return int|null
     */
    public function getExceptionStatusCode(\Exception $exception);

    /**
     * Returns an application-specific error code for a given Exception object.
     *
     * @param \Exception $exception
     *
     * @return string|null
     */
    public function getExceptionCode(\Exception $exception);

    /**
     * Returns a human-readable representation of the type of a given Exception object.
     *
     * @param \Exception $exception
     *
     * @return string|null
     */
    public function getExceptionType(\Exception $exception);

    /**
     * Returns a detailed human-readable representation of a given Exception object.
     *
     * @param \Exception $exception
     *
     * @return string|null
     */
    public function getExceptionText(\Exception $exception);
}
