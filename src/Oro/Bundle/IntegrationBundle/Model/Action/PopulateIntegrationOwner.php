<?php

namespace Oro\Bundle\IntegrationBundle\Model\Action;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

/**
 * Set owner for entity based on integration settings.
 *
 * Usage:
 * @populate_channel_owner:
 *      attribute: $lead
 *      integration: $lead.channel
 */
class PopulateIntegrationOwner extends AbstractAction
{
    const NAME = "populate_channel_owner";

    /**
     * @var mixed
     */
    protected $attribute;

    /**
     * @var mixed
     */
    protected $integration;

    /**
     * @var DefaultOwnerHelper
     */
    protected $defaultOwnerHelper;

    /**
     * @param ContextAccessor $contextAccessor
     * @param DefaultOwnerHelper $defaultOwnerHelper
     */
    public function __construct(ContextAccessor $contextAccessor, DefaultOwnerHelper $defaultOwnerHelper)
    {
        parent::__construct($contextAccessor);

        $this->defaultOwnerHelper = $defaultOwnerHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $entity = $this->contextAccessor->getValue($context, $this->attribute);
        if (!is_object($entity)) {
            throw new InvalidParameterException(
                sprintf(
                    'Action "%s" expects an entity in parameter "attribute", %s is given.',
                    self::NAME,
                    gettype($entity)
                )
            );
        }

        $integration = $this->contextAccessor->getValue($context, $this->integration);
        if (!$integration instanceof Integration) {
            throw new InvalidParameterException(
                sprintf(
                    'Action "%s" expects %s in parameter "integration", %s is given.',
                    self::NAME,
                    'Oro\\Bundle\\IntegrationBundle\\Entity\\Channel',
                    is_object($integration) ? get_class($integration) : gettype($integration)
                )
            );
        }

        $this->defaultOwnerHelper->populateChannelOwner($entity, $integration);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Parameter "attribute" is required.');
        }

        if (empty($options['integration'])) {
            throw new InvalidParameterException('Parameter "integration" is required.');
        }

        $this->attribute = $options['attribute'];
        $this->integration = $options['integration'];

        return $this;
    }
}
