<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\EntityExtend;

use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationNameGenerator as NameGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityStaticCache;

/**
 * Abstract Extended Entity Field Processor Associations Extension
 */
abstract class AbstractAssociationEntityFieldExtension implements EntityFieldExtensionInterface
{
    abstract protected function isApplicable(EntityFieldProcessTransport $transport): bool;

    abstract protected function getRelationKind(): ?string;

    abstract protected function getRelationType(): string;

    public function getMethods(EntityFieldProcessTransport $transport): array
    {
        $methods = [
            'getAssociationRelationType' => [$this, 'callGetRelationType'],
            'getAssociationRelationKind' => [$this, 'callGetRelationKind'],
        ];

        switch ($this->getRelationType()) {
            case RelationType::MANY_TO_MANY:
            case RelationType::MULTIPLE_MANY_TO_ONE:
                $methods[NameGenerator::generateSupportTargetMethodName($this->getRelationKind())] = [
                    $this,
                    'callSupport'
                ];
                $methods[NameGenerator::generateGetTargetsMethodName($this->getRelationKind())] = [
                    $this,
                    'callGetTargets'
                ];
                $methods[NameGenerator::generateHasTargetMethodName($this->getRelationKind())] = [
                    $this,
                    'callHasTarget'
                ];
                $methods[NameGenerator::generateAddTargetMethodName($this->getRelationKind())] = [
                    $this,
                    'callAddTarget'
                ];
                $methods[NameGenerator::generateRemoveTargetMethodName($this->getRelationKind())] = [
                    $this,
                    'callRemoveTarget'
                ];
                break;
            case RelationType::MANY_TO_ONE:
                $methods[NameGenerator::generateSupportTargetMethodName($this->getRelationKind())] = [
                    $this,
                    'callSupport'
                ];
                $methods[NameGenerator::generateGetTargetMethodName($this->getRelationKind())] = [
                    $this,
                    'callGetTarget'
                ];
                $methods[NameGenerator::generateSetTargetMethodName($this->getRelationKind())] = [
                    $this,
                    'callSetTarget'
                ];
                break;
        }

        return $methods;
    }

    protected function callGetRelationType(EntityFieldProcessTransport $transport): void
    {
        $transport->setResult($this->getRelationType());
    }

    protected function callGetRelationKind(EntityFieldProcessTransport $transport): void
    {
        $transport->setResult($this->getRelationKind());
    }

    protected function callSupport(EntityFieldProcessTransport $transport): void
    {
        $result = AssociationExtendEntity::support($transport->getObject(), $transport->getArgument(0));
        $transport->setResult($result);
    }

    protected function callGetTargets(EntityFieldProcessTransport $transport): void
    {
        $result = AssociationExtendEntity::getTargets($transport->getObject(), $transport->getArgument(0));
        $transport->setResult($result);
    }

    protected function callGetTarget(EntityFieldProcessTransport $transport): void
    {
        $result = AssociationExtendEntity::getTarget($transport->getObject());
        $transport->setResult($result);
    }

    protected function callSetTarget(EntityFieldProcessTransport $transport): void
    {
        AssociationExtendEntity::setTarget($transport->getObject(), $transport->getArgument(0));
        $transport->setResult($transport->getObject());
    }

    protected function callHasTarget(EntityFieldProcessTransport $transport): void
    {
        $result = AssociationExtendEntity::hasTarget($transport->getObject(), $transport->getArgument(0));
        $transport->setResult($result);
    }

    protected function callAddTarget(EntityFieldProcessTransport $transport): void
    {
        AssociationExtendEntity::addTarget($transport->getObject(), $transport->getArgument(0));
        $transport->setResult($transport->getObject());
    }

    protected function callRemoveTarget(EntityFieldProcessTransport $transport): void
    {
        AssociationExtendEntity::removeTarget($transport->getObject(), $transport->getArgument(0));
        $transport->setResult($transport->getObject());
    }

    /**
     * @inheritDoc
     */
    public function get(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isApplicable($transport)) {
            return;
        }
        if ($transport->getName() !== 'target') {
            return;
        }

        $this->callGetTarget($transport);

        $transport->setProcessed(true);
    }

    /**
     * @inheritDoc
     */
    public function set(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isApplicable($transport)) {
            return;
        }

        if ($transport->getName() !== 'target') {
            return;
        }
        $transport->setArguments([$transport->getValue()]);

        $this->callSetTarget($transport);

        $transport->setProcessed(true);
    }

    /**
     * @inheritDoc
     */
    public function call(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isApplicable($transport)) {
            return;
        }

        $methods = $this->getMethods($transport);
        if (isset($methods[$transport->getName()])) {
            call_user_func($methods[$transport->getName()], $transport);
            $transport->setProcessed(true);
        }
    }

    /**
     * @inheritDoc
     */
    public function isset(EntityFieldProcessTransport $transport): void
    {
    }

    /**
     * @inheritDoc
     */
    public function propertyExists(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isApplicable($transport)) {
            return;
        }

        if ($transport->getName() === 'target') {
            $transport->setResult(true);
            $transport->setProcessed(true);
        }
    }

    public function methodExists(EntityFieldProcessTransport $transport): void
    {
        if (!$this->isApplicable($transport)) {
            return;
        }
        // get a list of associated methods
        $methods = array_keys($this->getMethods($transport));
        if (EntityPropertyInfo::isMethodMatchExists($methods, $transport->getName())) {
            $transport->setResult(true);
            $transport->setProcessed(true);
            ExtendEntityStaticCache::setMethodExistsCache($transport, true);
        }
    }
}
