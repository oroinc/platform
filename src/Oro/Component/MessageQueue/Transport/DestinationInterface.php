<?php
namespace Oro\Component\MessageQueue\Transport;

/**
 * A Destination object encapsulates a provider-specific address.
 * The transport API does not define a standard address syntax.
 * Although a standard address syntax was considered,
 * it was decided that the differences in address semantics between existing message-oriented middleware (MOM)
 * products were too wide to bridge with a single syntax.
 *
 * Since Destination is an administered object,
 * it may contain provider-specific configuration information in addition to its address.
 *
 * @link https://docs.oracle.com/javaee/1.4/api/javax/jms/Destination.html
 */
interface DestinationInterface
{
}
