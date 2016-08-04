<?php
namespace Oro\Component\MessageQueue\Client\Meta;

use Oro\Component\MessageQueue\Client\Config;

class DestinationMetaRegistry
{
    /**
     * @var array
     */
    private $destinationsMeta;
    
    /**
     * @var Config
     */
    private $config;
    
    /**
     * @var string
     */
    private $defaultQueueClientName;

    /**
     * @param Config $config
     * @param array $destinationsMeta
     * @param string $defaultQueueClientName
     */
    public function __construct(Config $config, array $destinationsMeta, $defaultQueueClientName)
    {
        $this->config = $config;
        $this->destinationsMeta = $destinationsMeta;
        $this->defaultQueueClientName = $defaultQueueClientName;
    }

    /**
     * @param string $clientDestinationName
     *
     * @return DestinationMeta
     */
    public function getDestinationMeta($clientDestinationName)
    {
        if (false == array_key_exists($clientDestinationName, $this->destinationsMeta)) {
            throw new \InvalidArgumentException(sprintf(
                'The destination meta not found. Requested name `%s`',
                $clientDestinationName
            ));
        }

        $transportName = $this->defaultQueueClientName === $clientDestinationName ?
            $this->config->getDefaultQueueName() :
            $this->config->formatName($clientDestinationName)
        ;

        $destination = array_replace([
            'subscribers' => [],
            'transportName' => $transportName,
        ], $this->destinationsMeta[$clientDestinationName]);

        return new DestinationMeta($clientDestinationName, $destination['transportName'], $destination['subscribers']);
    }

    /**
     * @return \Generator|DestinationMeta[]
     */
    public function getDestinationsMeta()
    {
        foreach (array_keys($this->destinationsMeta) as $name) {
            yield $this->getDestinationMeta($name);
        }
    }
}
