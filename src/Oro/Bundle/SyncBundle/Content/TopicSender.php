<?php

namespace Oro\Bundle\SyncBundle\Content;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

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
        $userName = $this->tokenStorage->getToken() && is_object($this->tokenStorage->getToken()->getUser())
            ? $this->tokenStorage->getToken()->getUser()->getUserName()
            : null;

        if (!empty($tags)) {
            $tags = array_map(
                function ($tag) use ($userName) {
                    return ['username' => $userName, 'tagname' => $tag];
                },
                $tags
            );
            try {
                $this->publisher->send(self::UPDATE_TOPIC, json_encode($tags));
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
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
}
