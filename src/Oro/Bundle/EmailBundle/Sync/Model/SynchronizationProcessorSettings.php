<?php

namespace Oro\Bundle\EmailBundle\Sync\Model;

class SynchronizationProcessorSettings
{
    /** @var  bool In this mode all emails will be re-synced again for checked folders */
    protected $forceMode = false;

    protected $showMessage = false;

    public function __construct($forceMode, $showMessage)
    {
        $this->forceMode = $forceMode;
        $this->showMessage = $showMessage;
    }

    /**
     * Set force mode.
     *
     * @param bool $mode
     *
     * @return $this
     */
    public function setForceMode($mode)
    {
        $this->forceMode = $mode;

        return $this;
    }

    /**
     * Check is force mode enabled.
     */
    public function isForceMode()
    {
        return $this->forceMode === true;
    }

    public function setShowMessage($value)
    {
        return $this->showMessage = $value;
    }

    public function needShowMessage()
    {
        return $this->showMessage === true;
    }
}
