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
    private ?int $statusCode = null;
    private ?string $code = null;
    private string|Label|null $title = null;
    private string|Label|null $detail = null;
    private ?ErrorSource $source = null;
    private ?\Exception $innerException = null;

    /**
     * Creates an error object.
     *
     * @param string|Label|null $title  A short, human-readable summary of the problem that should not change
     *                                  from occurrence to occurrence of the problem.
     * @param string|Label|null $detail A human-readable explanation specific to this occurrence of the problem
     *
     * @return $this
     */
    public static function create(string|Label|null $title, string|Label|null $detail = null): static
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
     * @param int               $statusCode A status code should be returned for the error. Default value - 400
     *
     * @return $this
     */
    public static function createValidationError(
        string|Label $title,
        string|Label|null $detail = null,
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): static {
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
    public static function createConflictValidationError(string|Label|null $detail = null): static
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
    public static function createByException(\Exception $exception): static
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
     */
    public function setStatusCode(?int $statusCode): static
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
     */
    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Gets a short, human-readable summary of the problem that should not change
     * from occurrence to occurrence of the problem.
     */
    public function getTitle(): string|Label|null
    {
        return $this->title;
    }

    /**
     * Sets a short, human-readable summary of the problem that should not change
     * from occurrence to occurrence of the problem.
     */
    public function setTitle(string|Label|null $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets a human-readable explanation specific to this occurrence of the problem.
     */
    public function getDetail(): string|Label|null
    {
        return $this->detail;
    }

    /**
     * Sets a human-readable explanation specific to this occurrence of the problem.
     */
    public function setDetail(string|Label|null $detail): static
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
     */
    public function setSource(?ErrorSource $source): static
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
     */
    public function setInnerException(?\Exception $exception): static
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
