<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailAttachmentRepository;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class EmailApiEntityManager extends ApiEntityManager
{
    /**
     * @param string          $class
     * @param ObjectManager   $om
     */
    public function __construct(
        $class,
        ObjectManager $om
    ) {
        parent::__construct($class, $om);
    }

    /**
     * Gets email attachment entity by identifier.
     *
     * @param integer $id
     *
     * @return EmailAttachment
     */
    public function findEmailAttachment($id)
    {
        return $this->getEmailAttachmentRepository()->find($id);
    }

    /**
     * Gets email attachment repository
     *
     * @return EmailAttachmentRepository
     */
    public function getEmailAttachmentRepository()
    {
        return $this->getObjectManager()->getRepository('Oro\Bundle\EmailBundle\Entity\EmailAttachment');
    }

    /**
     * {@inheritdoc}
     */
    protected function getSerializationConfig()
    {
        $config = [
            'excluded_fields' => ['fromEmailAddress'],
            'fields'          => [
                'created'    => [
                    'result_name' => 'createdAt'
                ],
                'importance' => [
                    'data_transformer' => 'oro_email.email_importance_transformer'
                ],
                'fromName'   => [
                    'result_name' => 'from'
                ],
                'recipients' => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'name' => null,
                        'type' => null
                    ]
                ],
                'emailBody'  => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'bodyContent' => [
                            'result_name' => 'body'
                        ],
                        'bodyIsText'  => [
                            'data_transformer' => 'oro_email.email_body_type_transformer',
                            'result_name'      => 'bodyType'
                        ]
                    ]
                ],
                'emailUsers' => [
                    'exclusion_policy' => 'all',
                    'hints'            => ['HINT_FILTER_BY_CURRENT_USER'],
                    'fields'           => [
                        'id'       => null,
                        'seen'       => null,
                        'receivedAt' => null,
                        'origin'     => [
                            'exclusion_policy' => 'all',
                            'fields' =>
                                ['id' => null]
                        ],
                        'folders'     => [
                            'exclusion_policy' => 'all',
                            'fields'           => [
                                'id'       => null,
                                'fullName' => null,
                                'name'     => null,
                                'type'     => null
                            ]
                        ]
                    ]
                ]
            ],
            'post_serialize'  => function (array &$result) {
                $this->postSerializeEmail($result);
            }
        ];

        return $config;
    }

    /**
     * @param array $result
     */
    protected function postSerializeEmail(array &$result)
    {
        $result['to']  = [];
        $result['cc']  = [];
        $result['bcc'] = [];
        foreach ($result['recipients'] as $recipient) {
            $result[$recipient['type']][] = $recipient['name'];
        }
        unset($result['recipients']);

        if (empty($result['emailBody'])) {
            $result['body']     = null;
            $result['bodyType'] = null;
        } else {
            $result['body']     = $result['emailBody']['bodyContent'];
            $result['bodyType'] = $result['emailBody']['bodyIsText'];
        }
        unset($result['emailBody']);

        if (empty($result['emailUsers'])) {
            $result['seen']       = false;
            $result['receivedAt'] = null;
            $result['folders']    = [];
        } else {
            $emailUser            = reset($result['emailUsers']);
            $result['seen']       = $emailUser['seen'];
            $result['receivedAt'] = $emailUser['receivedAt'];
            $result['folders'] = [$emailUser['folders']];
        }
        unset($result['emailUsers']);
    }
}
