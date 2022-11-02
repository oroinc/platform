<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Extension\Theme\ResourceProvider\LastModificationDateProvider;

/**
 * Adds the last modification date of theme resources to the context.
 */
class LastModifiedDateContextConfigurator implements ContextConfiguratorInterface
{
    private const LAST_MODIFICATION_DATE = 'last_modification_date';

    /** @var LastModificationDateProvider */
    private $lastModificationDateProvider;

    public function __construct(LastModificationDateProvider $lastModificationDateProvider)
    {
        $this->lastModificationDateProvider = $lastModificationDateProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $context->getResolver()
            ->setDefaults([self::LAST_MODIFICATION_DATE => $date->format(\DateTime::COOKIE)])
            ->setAllowedTypes(self::LAST_MODIFICATION_DATE, 'string');

        $date = $this->lastModificationDateProvider->getLastModificationDate();
        if (null !== $date) {
            $context->set(self::LAST_MODIFICATION_DATE, $date->format(\DateTime::COOKIE));
        }
    }
}
