<?php

namespace Oro\Bundle\ApiBundle\Model;

use Oro\Bundle\ApiBundle\Request\Constraint;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Represents an error occurred when processing an API action.
 */
class Error
{
    /** @var int|null */
    private $statusCode;

    /** @var string|null */
    private $code;

    /** @var string|Label|null */
    private $title;

    /** @var string|Label|null */
    private $detail;

    /** @var ErrorSource|null */
    private $source;

    /** @var \Exception|null */
    private $innerException;

    /**
     * Creates an error object.
     *
     * @param string|Label      $title  A short, human-readable summary of the problem that should not change
     *                                  from occurrence to occurrence of the problem.
     * @param string|Label|null $detail A human-readable explanation specific to this occurrence of the problem
     *
     * @return $this
     */
    public static function create($title, $detail = null): Error
    {
        $error = new static();
        $error->setTitle($title);
        $error->setDetail($detail);

        return $error;
    }

    /**
     * Creates an error object that represents a violation of validation constraint.
     *
     * @param string|Label      $title      A short, human-readable summary of the problem that should not change
     *                                      from occurrence to occurrence of the problem.
     * @param string|Label|null $detail     A human-readable explanation specific to this occurrence of the problem
     * @param int|null          $statusCode A status code should be returned for the error. Default value - 400
     *
     * @return $this
     */
    public static function createValidationError(
        $title,
        $detail = null,
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): Error {
        return static::create($title, $detail)->setStatusCode($statusCode);
    }

    /**
     * Creates an error object that represents a violation of 409 Conflict validation constraint.
     * @link https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.4.10
     *
     * @param string|Label|null $detail A human-readable explanation specific to this occurrence of the problem
     *
     * @return $this
     */
    public static function createConflictValidationError($detail = null): Error
    {
        return static::create(Constraint::CONFLICT, $detail)->setStatusCode(Response::HTTP_CONFLICT);
    }

    /**
     * Creates an error object based on a given exception object.
     *
     * @param \Exception $exception An exception object that caused this occurrence of the problem
     *
     * @return $this
     */
    public static function createByException(\Exception $exception): Error
    {
        $error = new static();
        $error->setInnerException($exception);

        return $error;
    }

    /**
     * Gets the HTTP status code applicable to this problem.
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Sets the HTTP status code applicable to this problem.
     *
     * @param int|null $statusCode
     *
     * @return $this
     */
    public function setStatusCode(?int $statusCode): Error
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Gets an application-specific error code.
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Sets an application-specific error code.
     *
     * @param string|null $code
     *
     * @return $this
     */
    public function setCode(?string $code): Error
    {
        $this->code = $code;

        return $this;
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
     *
     * @return $this
     */
    public function setTitle($title): Error
    {
        $this->title = $title;

        return $this;
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
     *
     * @return $this
     */
    public function setDetail($detail): Error
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * Gets a source of this occurrence of the problem.
     */
    public function getSource(): ?ErrorSource
    {
        return $this->source;
    }

    /**
     * Sets a source of this occurrence of the problem.
     *
     * @param ErrorSource|null $source
     *
     * @return $this
     */
    public function setSource(ErrorSource $source = null): Error
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Gets an exception object that caused this occurrence of the problem.
     */
    public function getInnerException(): ?\Exception
    {
        return $this->innerException;
    }

    /**
     * Sets an exception object that caused this occurrence of the problem.
     *
     * @param \Exception|null $exception
     *
     * @return $this
     */
    public function setInnerException(\Exception $exception = null): Error
    {
        $this->innerException = $exception;

        return $this;
    }

    /**
     * Translates all attributes that are represented by the Label object.
     */
    public function trans(TranslatorInterface $translator): void
    {
        if ($this->title instanceof Label) {
            $this->title = $this->title->trans($translator);
        }
        if ($this->detail instanceof Label) {
            $this->detail = $this->detail->trans($translator);
        }
    }
}
