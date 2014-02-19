<?php

namespace Oro\Bundle\OrganizationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\LogicException;

class OwnerDeletionManagerPass implements CompilerPassInterface
{
    const SERVICE_KEY            = 'oro_organization.owner_deletion_manager';
    const ASSIGNMENT_CHECKER_TAG = 'oro_organization.owner_assignment_checker';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_KEY)) {
            return;
        }
        $managerDefinition = $container->getDefinition(self::SERVICE_KEY);

        $assignmentCheckers = $this->loadAssignmentCheckers($container);
        foreach ($assignmentCheckers as $entityClassName => $assignmentCheckerServiceId) {
            $managerDefinition->addMethodCall(
                'registerAssignmentChecker',
                [$entityClassName, new Reference($assignmentCheckerServiceId)]
            );
        }
    }

    /**
     * Load services which are owner assignment checkers
     *
     * @param ContainerBuilder $container
     * @return array
     */
    protected function loadAssignmentCheckers(ContainerBuilder $container)
    {
        $assignmentCheckers = [];
        $taggedServices     = $container->findTaggedServiceIds(self::ASSIGNMENT_CHECKER_TAG);
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $tagAttributes) {
                $this->assertTagHasAttribute(
                    $serviceId,
                    self::ASSIGNMENT_CHECKER_TAG,
                    $tagAttributes,
                    'entity'
                );
                $assignmentCheckers[$tagAttributes['entity']] = $serviceId;
            }
        }

        return $assignmentCheckers;
    }

    /**
     * @param string $serviceId
     * @param string $tagName
     * @param array  $tagAttributes
     * @param string $requiredAttribute
     * @throws LogicException
     */
    private function assertTagHasAttribute($serviceId, $tagName, array $tagAttributes, $requiredAttribute)
    {
        if (empty($tagAttributes[$requiredAttribute])) {
            throw new LogicException(
                sprintf('Tag "%s" for service "%s" must have attribute "%s"', $tagName, $serviceId, $requiredAttribute)
            );
        }
    }
}
