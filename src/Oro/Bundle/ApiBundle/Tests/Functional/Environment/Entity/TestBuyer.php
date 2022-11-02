<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * This entity is used to test associations that can have several types of the target entities,
 * including a target entity that is not accessible via API.
 * @ORM\Entity()
 * @Config()
 */
class TestBuyer extends TestPerson
{
}
