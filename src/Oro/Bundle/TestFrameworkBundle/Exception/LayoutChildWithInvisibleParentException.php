<?php

namespace Oro\Bundle\TestFrameworkBundle\Exception;

/**
 * Tells that child node with invisible parent that must stay unprocessed, was processed.
 */
class LayoutChildWithInvisibleParentException extends \Exception
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct(
            "Data providers for child node with invisible parent mustn't be called!"
        );
    }
}
