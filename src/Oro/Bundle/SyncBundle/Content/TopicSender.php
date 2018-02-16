<?php

namespace Oro\Bundle\SyncBundle\Content;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Component\DependencyInjection\ServiceLink;

class TopicSender
{
    const UPDATE_TOPIC = 'oro/data/update';

    /** @var TopicPublisher */
    protected $publisher;

    /** @var ServiceLink */
    protected $generatorLink;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param TopicPublisher        $publisher
     * @param ServiceLink           $generatorLink
     * @param TokenStorageInterface $tokenStorage
     * @param LoggerInterface       $logger
     */
    public function __construct(
        TopicPublisher $publisher,
        ServiceLink $generatorLink,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger
    ) {
        $this->publisher = $publisher;
        $this->generatorLink = $generatorLink;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
    }

    /**
     * Send payload into topic
     *
     * @param array $tags
     */
    public function send(array $tags)
    {
        if (!empty($tags)) {
            $userName = $this->getUserName();
            $tags = array_map(
                function ($tag) use ($userName) {
                    return ['username' => $userName, 'tagname' => $tag];
                },
                $tags
            );
            try {
                $this->publisher->send(self::UPDATE_TOPIC, json_encode($tags));
            } catch (\Exception $e) {
                $this->logger->error(
                    'Failed to publish a message to {topic}',
                    ['topic' => self::UPDATE_TOPIC, 'exception' => $e, 'tags' => $tags]
                );
            }
        }
    }

    /**
     * @return TagGeneratorChain
     */
    public function getGenerator()
    {
        return $this->generatorLink->getService();
    }

    /**
     * @return string|null
     */
    private function getUserName()
    {
        $userName = null;
        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                $userName = $user->getUserName();
            }
        }

        return $userName;
    }
}
