<?php

namespace Oro\Bundle\DraftBundle\Duplicator;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Represents a container for storing the parameters used in draft extensions
 */
final class DraftContext extends ArrayCollection implements \ArrayAccess
{
}
