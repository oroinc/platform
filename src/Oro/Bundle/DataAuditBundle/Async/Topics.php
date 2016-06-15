<?php
namespace Oro\Bundle\DataAuditBundle\Async;

final class Topics
{
    const ENTITIES_CHANGED = 'oro.data_audit.entities_changed';
    const ENTITIES_RELATIONS_CHANGED = 'oro.data_audit.entities_relations_changed';
    const ENTITIES_INVERSED_RELATIONS_CHANGED = 'oro.data_audit.entities_inversed_relations_changed';
}
