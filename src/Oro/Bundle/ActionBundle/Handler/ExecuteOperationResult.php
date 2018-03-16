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
    public function __construct(bool $success, string $code, ActionData $actionData)
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
    public function getActionData(): ActionData
    {
        return $this->actionData;
    }

    /**
     * @param bool $success
     */
    public function setSuccess(bool $success)
    {
        $this->success = $success;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param int $code
     */
    public function setCode(int $code)
    {
        $this->code = $code;
    }

    /**
     * @return ArrayCollection
     */
    public function getValidationErrors(): ArrayCollection
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
    public function setPageReload(bool $pageReload)
    {
        $this->pageReload = $pageReload;
    }

    /**
     * @return bool
     */
    public function isPageReload(): bool
    {
        return $this->pageReload;
    }

    /**
     * @return string
     */
    public function getExceptionMessage(): string
    {
        return $this->exceptionMessage;
    }

    /**
     * @param string $exceptionMessage
     */
    public function setExceptionMessage(string $exceptionMessage)
    {
        $this->exceptionMessage = $exceptionMessage;
    }
}
