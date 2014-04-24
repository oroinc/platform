<?php

namespace Oro\Bundle\IntegrationBundle\Manager;


use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ChannelDeleteManager
{
    /**
     * @var ChannelDeleteProviderInterface[]
     */
    protected $deleteProviders;

    /**
     * Add delete channel provider
     *
     * @param ChannelDeleteProviderInterface $deleteProvider
     */
    public function addProvider(ChannelDeleteProviderInterface $deleteProvider)
    {
        $this->deleteProviders[$deleteProvider->getSupportedChannelType()] = $deleteProvider;
    }

    /**
     * Delete channel
     *
     * @param Channel $channel
     * @return bool
     */
    public function deleteChannel(Channel $channel)
    {
        $channelType = $channel->getType();
        if (isset($this->deleteProviders[$channelType])) {
            return $this->deleteProviders[$channelType]->processDelete($channel);
        }

        return false;
    }
}
