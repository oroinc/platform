<?php

namespace Oro\Bundle\ApiBundle\Processor;

class Error
{
    /** @var string */
    protected $title;

    /** @var string */
    protected $detail;

    /** @var string */
    protected $status;

    /** @var \Exception */
    protected $innerException;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * @param string $detail
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return \Exception
     */
    public function getInnerException()
    {
        return $this->innerException;
    }

    /**
     * @param \Exception $innerException
     */
    public function setInnerException($innerException)
    {
        $this->innerException = $innerException;
    }
}
