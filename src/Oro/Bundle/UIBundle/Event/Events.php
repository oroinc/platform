<?php

namespace Oro\Bundle\UIBundle\Event;

final class Events
{
    public const BEFORE_VIEW_RENDER           = 'entity_view.render.before';
    public const BEFORE_UPDATE_FORM_RENDER    = 'entity_form.render.before';
    public const BEFORE_GROUPING_CHAIN_WIDGET = 'oro.ui.grouping_chain_widget.before';
}
