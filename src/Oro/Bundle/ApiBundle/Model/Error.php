<?php

namespace Oro\Bundle\ApiBundle\Model;

use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Represents an error happened during the processing of an action.
 */
class Error
{
    /** @var int|null */
    protected $statusCode;

    /** @var string|Label|null */
    protected $title;

    /** @var string|Label|null */
    protected $detail;

    /** @var \Exception|null */
    protected $innerException;

    /**
     * Gets the HTTP status code applicable to this problem.
     *
     * @return int|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Sets the HTTP status code applicable to this problem.
     *
     * @param int $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * Gets a short, human-readable summary of the problem that should not change
     * from occurrence to occurrence of the problem.
     *
     * @return string|Label|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets a short, human-readable summary of the problem that should not change
     * from occurrence to occurrence of the problem.
     *
     * @param string|Label $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Gets a human-readable explanation specific to this occurrence of the problem.
     *
     * @return string|Label|null
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * Sets a human-readable explanation specific to this occurrence of the problem.
     *
     * @param string|Label $detail
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;
    }

    /**
     * Gets an exception object that caused this occurrence of the problem.
     *
     * @return \Exception|null
     */
    public function getInnerException()
    {
        return $this->innerException;
    }

    /**
     * Sets an exception object that caused this occurrence of the problem.
     *
     * @param \Exception $exception
     */
    public function setInnerException(\Exception $exception)
    {
        $this->innerException = $exception;

        if (null === $this->statusCode) {
            $this->statusCode = ExceptionUtil::getExceptionStatusCode($exception);
        }
        if (null === $this->detail) {
            $this->detail = $exception->getMessage();
        }
    }

    /**
     * Translates all attributes that are represented by the Label object.
     *
     * @param TranslatorInterface $translator
     */
    public function trans(TranslatorInterface $translator)
    {
        if ($this->title instanceof Label) {
            $this->title = $this->title->trans($translator);
        }
        if ($this->detail instanceof Label) {
            $this->detail = $this->detail->trans($translator);
        }
    }
}
