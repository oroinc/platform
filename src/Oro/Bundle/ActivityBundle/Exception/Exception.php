<?php

namespace Oro\Bundle\ActivityBundle\Exception;

/**
 * Marker interface for all exceptions thrown by the ActivityBundle.
 *
 * This interface serves as a common contract for all activity-related exceptions,
 * allowing callers to catch and handle any exception originating from the ActivityBundle
 * using a single catch clause. Implementations should extend standard PHP exceptions
 * while implementing this interface to maintain consistency across the bundle.
 */
interface Exception
{
}
