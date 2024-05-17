<?php

namespace Oro\Component\Action\Model;

interface ActionDataStorageAwareInterface
{
    public function getActionDataStorage(): AbstractStorage;
}
