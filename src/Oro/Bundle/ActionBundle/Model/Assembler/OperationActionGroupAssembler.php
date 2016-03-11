<?php

namespace Oro\Bundle\ActionBundle\Model\Assembler;

use Oro\Bundle\ActionBundle\Model\OperationActionGroup;
use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;

class OperationActionGroupAssembler extends AbstractAssembler
{
    /** @var ConfigurationPassInterface */
    private $replacePropertyPath;

    /**
     * @param ConfigurationPassInterface $replacePropertyPath
     */
    public function __construct(ConfigurationPassInterface $replacePropertyPath)
    {
        $this->replacePropertyPath = $replacePropertyPath;
    }

    /**
     * @param array $configuration
     * @return OperationActionGroup[]
     */
    public function assemble(array $configuration)
    {
        $operationActionGroups = [];
        foreach ($configuration as $options) {
            // can be not unique items
            $operationActionGroups[] = $this->assembleOperationActionGroup($options);
        }

        return $operationActionGroups;
    }

    /**
     * @param array $options
     * @return OperationActionGroup
     */
    protected function assembleOperationActionGroup(array $options = [])
    {
        $this->assertOptions($options, ['name']);
        $operationActionGroup = new OperationActionGroup();
        $operationActionGroup
            ->setName($options['name'])
            ->setArgumentsMapping(
                $this->replacePropertyPath->passConfiguration(
                    $this->getOption(
                        $options,
                        'arguments_mapping',
                        []
                    )
                )
            );

        return $operationActionGroup;
    }
}
