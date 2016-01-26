<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class EmailOriginApiEntityManager extends ApiEntityManager
{
    /** @var array */
    private $emailOriginProperties;

    /**
     * {@inheritdoc}
     */
    protected function getSerializationConfig()
    {
        $config = [
            'exclusion_policy'     => 'all',
            'disable_partial_load' => true,
            'hints'                => ['HINT_FILTER_BY_CURRENT_USER'],
            'fields'               => [
                'id'                => null,
                '__discriminator__' => [
                    'result_name' => 'type'
                ],
                '__class__'         => [
                ],
                'isActive'          => [
                    'result_name' => 'active'
                ],
                'folders'           => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'id'          => null,
                        'fullName'    => null,
                        'name'        => null,
                        'type'        => null,
                        'syncEnabled' => null
                    ]
                ]
            ],
            'post_serialize'       => function (array &$result) {
                $this->postSerializeEmailOrigin($result);
            }
        ];

        foreach (array_unique(call_user_func_array('array_merge', $this->getEmailOriginProperties())) as $prop) {
            $config['fields'][$prop] = null;
        }

        return $config;
    }

    /**
     * @param array $result
     */
    protected function postSerializeEmailOrigin(array &$result)
    {
        $properties = [];
        foreach ($this->getEmailOriginProperties($result['__class__']) as $prop) {
            $properties[$prop] = $result[$prop];
            unset($result[$prop]);
        }
        $result['properties'] = $properties;

        unset($result['__class__']);
    }

    /**
     * @param string|null $className
     *
     * @return array
     */
    protected function getEmailOriginProperties($className = null)
    {
        if (null === $this->emailOriginProperties) {
            $metadata     = $this->getMetadata();
            $parentFields = $metadata->getFieldNames();

            // @todo: need to find a better way to exclude a password
            $parentFields[] = 'password';

            $this->emailOriginProperties = [];
            foreach ($metadata->discriminatorMap as $inheritedClassName) {
                $this->emailOriginProperties[$inheritedClassName] = array_diff(
                    $this->getObjectManager()->getClassMetadata($inheritedClassName)->getFieldNames(),
                    $parentFields
                );
            }
        }

        return null !== $className
            ? $this->emailOriginProperties[$className]
            : $this->emailOriginProperties;
    }
}
