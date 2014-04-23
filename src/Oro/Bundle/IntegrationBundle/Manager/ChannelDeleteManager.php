<?php

namespace Oro\Bundle\IntegrationBundle\Manager;


use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ChannelDeleteManager
{
    /**
     * @var ChannelDeleteProviderInterface[]
     */
    protected $deleteProviders;

    public function addProvider(ChannelDeleteProviderInterface $deleteProvider)
    {
        $this->deleteProviders[] = $deleteProvider;
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
        /** @var $deleteProvider ChannelDeleteProviderInterface */
        foreach ($this->deleteProviders as $deleteProvider) {
            if ($deleteProvider->getSupportedChannelType() == $channelType) {
                return $deleteProvider->processDelete($channel);
            }
        }

        return false;
    }
}
