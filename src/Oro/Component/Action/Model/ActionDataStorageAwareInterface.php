<?php

namespace Oro\Component\Action\Model;

/**
 * Represents class that holds ActionDataStorage.
 */
interface ActionDataStorageAwareInterface
{
    public function getActionDataStorage(): AbstractStorage;
}
