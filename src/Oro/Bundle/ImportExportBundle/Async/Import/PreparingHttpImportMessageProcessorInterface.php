<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

interface PreparingHttpImportMessageProcessorInterface
{
    /**
     * @return string
     */
    public static function getTopicForChildJob();

    /**
     * @return string
     */
    public static function getMessageName();
}
