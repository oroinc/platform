<?php

namespace Oro\Bundle\IntegrationBundle\Generator\Prefixed;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;

/**
 * Generates an integration identifier using unique prefix for each integration.
 */
class PrefixedIntegrationIdentifierGenerator implements IntegrationIdentifierGeneratorInterface
{
    private string $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * {@inheritDoc}
     */
    public function generateIdentifier(Channel $channel): string
    {
        return sprintf('%s_%s', $this->prefix, $channel->getId());
    }

    /**
     * @param string $identifier
     *
     * @return array{?string,?int} Array with payment method prefix and corresponding integration channel ID.
     */
    public static function parseIdentifier(string $identifier): array
    {
        $pos = strrpos($identifier, '_');
        if ($pos !== false) {
            $id = substr($identifier, $pos + 1);
            if (is_numeric($id)) {
                $prefix = substr($identifier, 0, $pos);
                $id = (int) $id;
            } else {
                $prefix = $identifier;
                $id = null;
            }
        }

        return [$prefix ?? null, $id ?? null];
    }
}
