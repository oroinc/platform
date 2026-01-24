<?php

namespace Oro\Bundle\UserBundle\Api\Model;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * Represents a user profile model for API operations.
 *
 * This model extends the {@see User} entity to provide a specialized representation
 * of user data for API endpoints, allowing for controlled exposure of user
 * profile information through the API layer.
 */
class UserProfile extends User
{
}
