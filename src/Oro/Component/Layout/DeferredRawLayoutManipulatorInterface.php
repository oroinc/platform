<?php

namespace Oro\Component\Layout;

/**
 * Provides a set of methods to manipulate the layout and apply the changes on demand
 * In additional to LayoutManipulatorInterface provides methods to manage the layout item options
 * The options related operation are available for the layout built without the block types
 */
interface DeferredRawLayoutManipulatorInterface extends
    DeferredLayoutManipulatorInterface,
    RawLayoutManipulatorInterface
{
}
