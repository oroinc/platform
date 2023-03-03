<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

/**
 * Entity field extension interface.
 */
interface EntityFieldExtensionInterface
{
    public function get(EntityFieldProcessTransport $transport): void;

    public function set(EntityFieldProcessTransport $transport): void;

    public function call(EntityFieldProcessTransport $transport): void;

    public function isset(EntityFieldProcessTransport $transport): void;

    public function propertyExists(EntityFieldProcessTransport $transport): void;

    public function methodExists(EntityFieldProcessTransport $transport): void;

    public function getMethods(EntityFieldProcessTransport $transport): array;
}
