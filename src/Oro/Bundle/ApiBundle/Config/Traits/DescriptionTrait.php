<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @property array $items
 */
trait DescriptionTrait
{
    /**
     * Indicates whether the description attribute exists.
     *
     * @return bool
     */
    public function hasDescription()
    {
        return array_key_exists(ConfigUtil::DESCRIPTION, $this->items);
    }

    /**
     * Gets the value of the description of the entity.
     *
     * @return string|Label|null
     */
    public function getDescription()
    {
        return array_key_exists(ConfigUtil::DESCRIPTION, $this->items)
            ? $this->items[ConfigUtil::DESCRIPTION]
            : null;
    }

    /**
     * Sets the value of the description of the entity.
     *
     * @param string|Label|null $description
     */
    public function setDescription($description)
    {
        if ($description) {
            $this->items[ConfigUtil::DESCRIPTION] = $description;
        } else {
            unset($this->items[ConfigUtil::DESCRIPTION]);
        }
    }
}
