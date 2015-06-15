<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailAttachmentRepository;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class EmailApiEntityManager extends ApiEntityManager
{
    /**
     * Get email attachment entity by identifier.
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
     * Get email attachment repository
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
                'folders'    => [
                    'exclusion_policy' => 'all',
                    'fields'           => [
                        'origin'   => ['fields' => 'id'],
                        'fullName' => null,
                        'name'     => null,
                        'type'     => null
                    ]
                ],
                'created'   => [
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
            $result['body']     = $result['emailBody'][0]['body'];
            $result['bodyType'] = $result['emailBody'][0]['bodyType'];
        }
        unset($result['emailBody']);
    }
}
