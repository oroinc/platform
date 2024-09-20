<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Enum otpion field extension.
 */
class EnumOptionFieldExtension extends AbstractEntityFieldExtension implements EntityFieldExtensionInterface
{
    protected const string METHOD_NAME = 'getDefaultName';
    protected const string PROPERTY_NAME = 'defaultName';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    protected function isApplicable(EntityFieldProcessTransport $transport): bool
    {
        if ($transport->getClass() !== EnumOption::class
            && !$transport->getObject() instanceof EnumOptionInterface) {
            return false;
        }

        return true;
    }

    public function get(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isApplicable($transport) || $transport->getName() !== self::PROPERTY_NAME) {
            return;
        }
        $transport->setProcessed(true);
        $transport->setResult(
            $this->translator->trans(ExtendHelper::buildEnumOptionTranslationKey($transport->getObjectVar('id')))
        );
    }

    public function set(EntityFieldProcessTransport $transport): void
    {
    }

    public function call(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isApplicable($transport)) {
            return;
        }
        if ($transport->getName() === static::METHOD_NAME) {
            $transport->setProcessed(true);
            $transport->setResult(
                $this->translator->trans(
                    ExtendHelper::buildEnumOptionTranslationKey($transport->getObjectVar('id'))
                )
            );
        }
    }

    public function propertyExists(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isApplicable($transport) || $transport->getName() !== self::PROPERTY_NAME) {
            return;
        }
        $transport->setProcessed(true);
        $transport->setResult(true);
    }

    public function methodExists(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isApplicable($transport) || $transport->getName() !== self::METHOD_NAME) {
            return;
        }
        $transport->setProcessed(true);
        $transport->setResult(true);
    }
}
