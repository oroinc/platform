<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\AbstractExclusionProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Exclusion provider that determines if an entity should be available when creating an email template.
 */
class AvailableInTemplateEntityExclusionProvider extends AbstractExclusionProvider
{
    public function __construct(
        private readonly ConfigProvider $configProvider,
    ) {
    }

    #[\Override]
    public function isIgnoredEntity($className): bool
    {
        $entityConfig = $this->configProvider->getConfig($className);

        return ((bool) $entityConfig->get('available_in_template')) === false;
    }
}
