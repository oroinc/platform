<?php

namespace Oro\Bundle\ImportExportBundle\Async\Topic;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Topic for processing the results of import or export before they are stored.
 */
class SaveImportExportResultTopic extends AbstractTopic
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public static function getName(): string
    {
        return 'oro.importexport.save_import_export_result';
    }

    public static function getDescription(): string
    {
        return 'Processes the results of import or export before they are stored';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'jobId',
                'entity',
                'type',
                'userId',
                'owner',
                'options',
            ])
            ->setRequired([
                'jobId',
                'entity',
                'type',
            ])
            ->setDefaults([
                'userId' => null,
                'owner' => fn (Options $options) => $this->findUserById($options['userId']),
                'options' => [],
            ])
            ->addAllowedTypes('jobId', 'int')
            ->addAllowedTypes('entity', 'string')
            ->addAllowedTypes('type', 'string')
            ->addAllowedTypes('userId', ['int', 'null'])
            ->addAllowedTypes('options', 'array')
            ->addAllowedValues(
                'type',
                [
                    ProcessorRegistry::TYPE_EXPORT,
                    ProcessorRegistry::TYPE_IMPORT,
                    ProcessorRegistry::TYPE_IMPORT_VALIDATION,
                ]
            );
    }

    private function findUserById($userId = null): ?User
    {
        return $userId ? $this->userManager->findUserBy(['id' => $userId]) : null;
    }
}
