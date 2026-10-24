<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Processor\EnumOption;

use Oro\Bundle\ApiBundle\Api\Repository\EnumOptionRepository;
use Oro\Bundle\ApiBundle\Processor\Delete\DeleteContext;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Prevents deletion of options from system or immutable enums.
 */
class ValidateEnumOptionDeletionAccess implements ProcessorInterface
{
    public function __construct(
        private readonly EnumOptionRepository $repository
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var DeleteContext $context */

        $enumOption = $context->getResult();
        if (!$enumOption instanceof EnumOption) {
            return;
        }

        $configs = $this->repository->getConfigs($enumOption->getEnumCode());
        if ($this->repository->isSystemEnum($configs)) {
            throw new AccessDeniedException('Options cannot be deleted from a system enum.');
        }
        if (!$this->isDeletionAllowed($configs, $enumOption)) {
            throw new AccessDeniedException('The enum option is immutable and cannot be deleted.');
        }
    }

    private function isDeletionAllowed(array $configs, EnumOption $enumOption): bool
    {
        $isDeletionAllowed = true;
        /** @var ConfigInterface $config */
        foreach ($configs as $config) {
            if ($this->isDeletionBlocked($config, $enumOption)) {
                $isDeletionAllowed = false;
                break;
            }
        }

        return $isDeletionAllowed;
    }

    private function isDeletionBlocked(ConfigInterface $config, EnumOption $enumOption): bool
    {
        $immutable = $config->get('immutable');
        if (true === $immutable || (\is_array($immutable) && \in_array('delete', $immutable, true))) {
            return true;
        }

        $immutableCodes = $config->get('immutable_codes', false, []);

        return
            \in_array($enumOption->getId(), $immutableCodes, true)
            || \in_array($enumOption->getInternalId(), $immutableCodes, true);
    }
}
