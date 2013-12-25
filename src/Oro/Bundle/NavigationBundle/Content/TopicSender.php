<?php

namespace Oro\Bundle\NavigationBundle\Content;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class TopicSender
{
    const UPDATE_TOPIC = 'oro/data/update';

    /** @var TopicPublisher */
    protected $publisher;

    /** @var ServiceLink */
    protected $generatorLink;

    /** @var ServiceLink */
    protected $securityContextLink;

    public function __construct(
        TopicPublisher $publisher,
        ServiceLink $generatorLink,
        ServiceLink $securityContextLink
    ) {
        $this->publisher           = $publisher;
        $this->generatorLink       = $generatorLink;
        $this->securityContextLink = $securityContextLink;
    }

    /**
     * Send payload into topic
     *
     * @param array $tags
     */
    public function send(array $tags)
    {
        /** @var SecurityContextInterface $securityContext */
        $securityContext = $this->securityContextLink->getService();
        $userName        = $securityContext->getToken() && is_object($securityContext->getToken()->getUser())
            ? $securityContext->getToken()->getUser()->getUserName() : null;

        if (!empty($tags)) {
            $tags = array_map(
                function ($tag) use ($userName) {
                    return ['username' => $userName, 'tagname' => $tag];
                },
                $tags
            );
            $this->publisher->send(self::UPDATE_TOPIC, json_encode($tags));
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
