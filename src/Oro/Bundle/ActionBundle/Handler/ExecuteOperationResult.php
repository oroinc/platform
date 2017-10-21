<?php

namespace Oro\Bundle\ActionBundle\Handler;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Model\ActionData;

/**
 * Store and provide access to operation's execution result data.
 */
class ExecuteOperationResult
{
    /** @var ActionData */
    protected $actionData;

    /** @var bool */
    protected $success;

    /** @var int */
    protected $code;

    /** @var string */
    protected $exceptionMessage = '';

    /** @var ArrayCollection */
    protected $validationErrors;

    /** @var bool */
    protected $pageReload = true;

    /**
     * @param bool       $success
     * @param string     $code
     * @param ActionData $actionData
     */
    public function __construct($success, $code, ActionData $actionData)
    {
        $this->success          = $success;
        $this->code             = $code;
        $this->actionData       = $actionData;
        $this->validationErrors = new ArrayCollection();
    }

    /**
     * @param ActionData $actionData
     */
    public function setActionData(ActionData $actionData)
    {
        $this->actionData = $actionData;
    }

    /**
     * @return ActionData
     */
    public function getActionData()
    {
        return $this->actionData;
    }

    /**
     * @param bool $success
     */
    public function setSuccess($success)
    {
        $this->success = $success;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param int $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return ArrayCollection
     */
    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    /**
     * @param ArrayCollection $validationErrors
     */
    public function setValidationErrors(ArrayCollection $validationErrors)
    {
        $this->validationErrors = $validationErrors;
    }

    /**
     * @param bool $pageReload
     */
    public function setPageReload($pageReload)
    {
        $this->pageReload = $pageReload;
    }

    /**
     * @return bool
     */
    public function isPageReload()
    {
        return $this->pageReload;
    }

    /**
     * @return string
     */
    public function getExceptionMessage()
    {
        return $this->exceptionMessage;
    }

    /**
     * @param string $exceptionMessage
     */
    public function setExceptionMessage($exceptionMessage)
    {
        $this->exceptionMessage = $exceptionMessage;
    }
}
