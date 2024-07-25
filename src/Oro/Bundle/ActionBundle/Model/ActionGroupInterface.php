<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Exception\ForbiddenActionGroupException;

/**
 * ActionGroup service interface. ActionGroups are services that contain common logic used by actions and workflows.
 */
interface ActionGroupInterface
{
    /**
     * @param ActionData $data
     * @param Collection|null $errors
     * @return ActionData
     * @throws ForbiddenActionGroupException
     */
    public function execute(ActionData $data, Collection $errors = null): ActionData;

    /**
     * @return ActionGroupDefinition
     */
    public function getDefinition(): ActionGroupDefinition;

    /**
     * Check is actionGroup is allowed to execute
     *
     * @param ActionData $data
     * @param Collection|null $errors
     * @return bool
     */
    public function isAllowed(ActionData $data, Collection $errors = null): bool;

    /**
     * @return array|Parameter[]
     */
    public function getParameters(): array;
}
