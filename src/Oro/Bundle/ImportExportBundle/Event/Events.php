<?php

namespace Oro\Bundle\ImportExportBundle\Event;

final class Events
{
    const READ_ENTITY = 'oro.import_export.read_entity';
    const NORMALIZE_ENTITY = 'oro.import_export.normalize_entity';
    const DENORMALIZE_ENTITY = 'oro.import_export.denormalize_entity';
    const LOAD_ENTITY_RULES_AND_BACKEND_HEADERS = 'oro.import_export.load_entity_rules_and_backend_headers';
    const LOAD_TEMPLATE_FIXTURES = 'oro.import_export.load_template_fixtures';
}
