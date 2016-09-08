<?php

namespace Oro\Bundle\EmailBundle\Sync\Model;

class SynchronizationProcessorSettings
{
    /** @var  bool In this mode all emails will be re-synced again for checked folders */
    protected $forceMode = false;

    /** @var bool Allows to define show or hide log messages during resync of emails */
    protected $showMessage = false;

    /**
     * @param bool $forceMode
     * @param bool $showMessage
     */
    public function __construct($forceMode = false, $showMessage = false)
    {
        $this->forceMode = $forceMode;
        $this->showMessage = $showMessage;
    }

    /**
     * Set force mode.
     *
     * @param bool $mode
     */
    public function setForceMode($mode)
    {
        $this->forceMode = $mode;
    }

    /**
     * Check is force mode enabled.
     */
    public function isForceMode()
    {
        return $this->forceMode === true;
    }

    /**
     * Set value to show or hide log messages
     *
     * @param bool $value
     */
    public function setShowMessage($value)
    {
        $this->showMessage = $value;
    }

    /**
     * Check value is true.
     *
     * @return bool
     */
    public function needShowMessage()
    {
        return $this->showMessage === true;
    }
}
