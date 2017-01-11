<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PreparingHttpImportValidationMessageProcessor extends AbstractPreparingHttpImportMessageProcessor
{
    /**
     * @return string
     */
    public static function getMessageName()
    {
        return 'oro:import_validation:http';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::IMPORT_HTTP_VALIDATION_PREPARING];
    }
    /**
     * {@inheritdoc}
     */
    public static function getTopicForChildJob()
    {
        return Topics::IMPORT_HTTP_VALIDATION;
    }
}
