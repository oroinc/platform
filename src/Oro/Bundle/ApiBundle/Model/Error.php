<?php

namespace Oro\Bundle\ApiBundle\Model;

class Error
{
    /**
     * A human-readable summary of the problem. May be translatable.
     *
     * @var string
     */
    protected $title;

    /**
     * A human-readable explanation specific to this occurrence of the problem.
     * @var string
     */
    protected $detail;

    /**
     * The HTTP status code.
     *
     * @var integer
     */
    protected $status;

    /** @var \Exception */
    protected $innerException;

    /**
     * Gets title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets title.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Gets detail.
     *
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * Sets detail.
     *
     * @param string $detail
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;
    }

    /**
     * Gets status code.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets status code.
     *
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Gets inner exception.
     *
     * @return \Exception
     */
    public function getInnerException()
    {
        return $this->innerException;
    }

    /**
     * Sets inner exception.
     *
     * @param \Exception $innerException
     */
    public function setInnerException($innerException)
    {
        $this->innerException = $innerException;
    }
}
