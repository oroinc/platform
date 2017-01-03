<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ImportExportBundle\Async\Topics;

class PreparingHttpImportMessageProcessor extends AbstractPreparingHttpImportMessageProcessor
{
    /**
     * {@inheritdoc}
     */
    public static function getMessageName()
    {
        return 'oro:import:http';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::IMPORT_HTTP_PREPARING];
    }

    /**
     * {@inheritdoc}
     */
    public static function getTopicForChildJob()
    {
        return Topics::IMPORT_HTTP;
    }
}
