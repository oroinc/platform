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

    public function __construct(bool $success, string $code, ActionData $actionData)
    {
        $this->success          = $success;
        $this->code             = $code;
        $this->actionData       = $actionData;
        $this->validationErrors = new ArrayCollection();
    }

    public function setActionData(ActionData $actionData)
    {
        $this->actionData = $actionData;
    }

    public function getActionData(): ActionData
    {
        return $this->actionData;
    }

    public function setSuccess(bool $success)
    {
        $this->success = $success;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code)
    {
        $this->code = $code;
    }

    public function getValidationErrors(): ArrayCollection
    {
        return $this->validationErrors;
    }

    public function setValidationErrors(ArrayCollection $validationErrors)
    {
        $this->validationErrors = $validationErrors;
    }

    public function setPageReload(bool $pageReload)
    {
        $this->pageReload = $pageReload;
    }

    public function isPageReload(): bool
    {
        return $this->pageReload;
    }

    public function getExceptionMessage(): string
    {
        return $this->exceptionMessage;
    }

    public function setExceptionMessage(string $exceptionMessage)
    {
        $this->exceptionMessage = $exceptionMessage;
    }
}
