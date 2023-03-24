<?php

namespace Oro\Bundle\ImportExportBundle\Async\Topic;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Topic for generating a list of records for export which are later used in child job.
 */
class PreExportTopic extends AbstractTopic implements JobAwareTopicInterface
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public static function getName(): string
    {
        return 'oro.importexport.pre_export';
    }

    public static function getDescription(): string
    {
        return 'Generates a list of records for export which are later used in child job';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'jobName',
                'processorAlias',
                'outputFormat',
                'organizationId',
                'exportType',
                'options',
                'outputFilePrefix',
                'userId',
            ])
            ->setRequired([
                'jobName',
                'processorAlias',
            ])
            ->setDefaults([
                'outputFormat' => 'csv',
                'exportType' => ProcessorRegistry::TYPE_EXPORT,
                'options' => [],
                'outputFilePrefix' => null,
            ])
            ->addAllowedTypes('jobName', 'string')
            ->addAllowedTypes('processorAlias', 'string')
            ->addAllowedTypes('outputFormat', 'string')
            ->addAllowedTypes('exportType', 'string')
            ->addAllowedTypes('organizationId', ['int', 'null'])
            ->addAllowedTypes('options', 'array')
            ->addAllowedTypes('outputFilePrefix', ['string', 'null'])
            ->addAllowedTypes('userId', 'int');
    }

    public function createJobName($messageBody): string
    {
        return sprintf('oro_importexport.pre_export.%s.user_%s', $messageBody['jobName'], $this->getUser()->getId());
    }

    protected function getUser()
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new \RuntimeException('Security token is null');
        }

        $user = $token->getUser();

        if (!is_object($user) || !$user instanceof UserInterface || !method_exists($user, 'getId')) {
            throw new \RuntimeException('Not supported user type');
        }

        return $user;
    }
}
