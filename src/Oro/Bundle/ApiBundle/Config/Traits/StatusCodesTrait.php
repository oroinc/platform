<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @property array $items
 */
trait StatusCodesTrait
{
    /**
     * Gets response status codes.
     *
     * @return StatusCodesConfig|null
     */
    public function getStatusCodes()
    {
        return array_key_exists(ConfigUtil::STATUS_CODES, $this->items)
            ? $this->items[ConfigUtil::STATUS_CODES]
            : null;
    }

    /**
     * Sets response status codes.
     *
     * @param StatusCodesConfig|null $statusCodes
     */
    public function setStatusCodes(StatusCodesConfig $statusCodes = null)
    {
        if (null !== $statusCodes) {
            $this->items[ConfigUtil::STATUS_CODES] = $statusCodes;
        } else {
            unset($this->items[ConfigUtil::STATUS_CODES]);
        }
    }
}
