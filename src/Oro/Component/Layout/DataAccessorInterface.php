<?php

namespace Oro\Component\Layout;

/**
 * Provides array-like access to layout data.
 *
 * This interface extends PHP's ArrayAccess to allow blocks and block types to access layout data
 * using array notation, providing a convenient way to retrieve context and other data during layout building.
 */
interface DataAccessorInterface extends \ArrayAccess
{
}
