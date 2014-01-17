<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

interface TransportInterface
{
    /**
     * @param Channel $channel
     */
    public function init(Channel $channel);

    /**
     * @param string $action
     * @param array  $params
     *
     * @return mixed
     */
    public function call($action, array $params = []);

    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel();

    /**
     * Returns form type name needed to setup transport
     *
     * @return string
     */
    public function getSettingsFormType();

    /**
     * Returns entity name needed to store transport settings
     *
     * @return string
     */
    public function getSettingsEntityFQCN();
}
