<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Templating\EngineInterface;

use Nelmio\ApiDocBundle\Formatter\AbstractFormatter;

class HtmlFormatter extends AbstractFormatter
{
    /** @var FileLocatorInterface */
    protected $fileLocator;

    /** @var EngineInterface */
    protected $engine;

    /** @var string */
    protected $apiName;

    /** @var string */
    protected $endpoint;

    /** @var boolean */
    protected $enableSandbox;

    /** @var array */
    protected $requestFormats;

    /** @var string */
    protected $requestFormatMethod;

    /** @var string */
    protected $defaultRequestFormat;

    /** @var string */
    protected $acceptType;

    /** @var array */
    protected $bodyFormats;

    /** @var string */
    protected $defaultBodyFormat;

    /** @var array */
    protected $authentication;

    /** @var string */
    protected $motdTemplate;

    /** @var boolean */
    protected $defaultSectionsOpened;

    /**
     * @param FileLocatorInterface $fileLocator
     */
    public function setFileLocator(FileLocatorInterface $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

    /**
     * @param EngineInterface $engine
     */
    public function setTemplatingEngine(EngineInterface $engine)
    {
        $this->engine = $engine;
    }

    /**
     * @param string $apiName
     */
    public function setApiName($apiName)
    {
        $this->apiName = $apiName;
    }

    /**
     * @param string $endpoint
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * @param boolean $enableSandbox
     */
    public function setEnableSandbox($enableSandbox)
    {
        $this->enableSandbox = $enableSandbox;
    }

    /**
     * @param array $formats
     */
    public function setRequestFormats(array $formats)
    {
        $this->requestFormats = $formats;
    }

    /**
     * @param string $method
     */
    public function setRequestFormatMethod($method)
    {
        $this->requestFormatMethod = $method;
    }

    /**
     * @param string $format
     */
    public function setDefaultRequestFormat($format)
    {
        $this->defaultRequestFormat = $format;
    }

    /**
     * @param string $acceptType
     */
    public function setAcceptType($acceptType)
    {
        $this->acceptType = $acceptType;
    }

    /**
     * @param array $bodyFormats
     */
    public function setBodyFormats(array $bodyFormats)
    {
        $this->bodyFormats = $bodyFormats;
    }

    /**
     * @param string $defaultBodyFormat
     */
    public function setDefaultBodyFormat($defaultBodyFormat)
    {
        $this->defaultBodyFormat = $defaultBodyFormat;
    }

    /**
     * @param array $authentication
     */
    public function setAuthentication(array $authentication = null)
    {
        $this->authentication = $authentication;
    }

    /**
     * @param string $motdTemplate
     */
    public function setMotdTemplate($motdTemplate)
    {
        $this->motdTemplate = $motdTemplate;
    }

    /**
     * @param boolean $defaultSectionsOpened
     */
    public function setDefaultSectionsOpened($defaultSectionsOpened)
    {
        $this->defaultSectionsOpened = $defaultSectionsOpened;
    }

    /**
     * {@inheritdoc}
     */
    protected function renderOne(array $data)
    {
        return $this->engine->render('NelmioApiDocBundle::resource.html.twig', array_merge(
            [
                'data'           => $this->reformatData($data),
                'displayContent' => true,
            ],
            $this->getGlobalVars()
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function render(array $collection)
    {
        return $this->engine->render('NelmioApiDocBundle::resources.html.twig', array_merge(
            [
                'resources' => $this->reformatDocData($collection),
            ],
            $this->getGlobalVars()
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function compressNestedParameters(array $data, $parentName = null, $ignoreNestedReadOnly = false)
    {
        $newParams = array();
        foreach ($data as $name => $info) {
            $newName = $this->getNewName($name, $info, $parentName);

            $newParams[$newName] = array(
                'dataType'     => $info['dataType'],
                'readonly'     => $info['readonly'],
                'required'     => $info['required'],
                'description'  => $info['description'],

                'isRelation'     => $info['isRelation'],
                'isCollection'   => $info['isCollection']
            );

            if (isset($info['children']) && (!$info['readonly'] || !$ignoreNestedReadOnly)) {
                $children = $this->compressNestedParameters($info['children'], $newName, $ignoreNestedReadOnly);
                foreach ($children as $nestedItemName => $nestedItemData) {
                    $newParams[$nestedItemName] = $nestedItemData;
                }
            }
        }

        return $newParams;
    }

    /**
     * @return array
     */
    protected function getGlobalVars()
    {
        return [
            'apiName'               => $this->apiName,
            'authentication'        => $this->authentication,
            'endpoint'              => $this->endpoint,
            'enableSandbox'         => $this->enableSandbox,
            'requestFormatMethod'   => $this->requestFormatMethod,
            'acceptType'            => $this->acceptType,
            'bodyFormats'           => $this->bodyFormats,
            'defaultBodyFormat'     => $this->defaultBodyFormat,
            'requestFormats'        => $this->requestFormats,
            'defaultRequestFormat'  => $this->defaultRequestFormat,
            'date'                  => date(DATE_RFC822),
            'css'                   => $this->getCss(),
            'js'                    => $this->getJs(),
            'motdTemplate'          => $this->motdTemplate,
            'defaultSectionsOpened' => $this->defaultSectionsOpened,
        ];
    }

    /**
     * @return string
     */
    protected function getCss()
    {
        return $this->getFileContent('@NelmioApiDocBundle/Resources/public/css/screen.css');
    }

    /**
     * @return string
     */
    protected function getJs()
    {
        $result = $this->getFileContent('@NelmioApiDocBundle/Resources/public/js/all.js');
        $result .= "\n" . $this->getFileContent('@OroApiBundle/Resources/public/lib/jquery.bind-first-0.2.3.min.js');
        if ($this->enableSandbox) {
            $result .= "\n" . $this->getFileContent('@OroApiBundle/Resources/public/lib/wsse.js');
        }

        return $result;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function getFileContent($path)
    {
        return file_get_contents($this->fileLocator->locate($path));
    }

    /**
     * Reformats input and output data for collected array of resources.
     *
     * @param array $collection
     *
     * @return array
     */
    protected function reformatDocData(array $collection)
    {
        foreach ($collection as $resourceBlockName => $resourceGroupBlock) {
            foreach ($resourceGroupBlock as $resourceUrl => $resourceBlock) {
                foreach ($resourceBlock as $resourceId => $resource) {
                    $collection[$resourceBlockName][$resourceUrl][$resourceId] = $this->reformatData($resource);
                }
            }
        }

        return $collection;
    }

    /**
     * Reformats input and output data for collected resource.
     *
     * @param array $data
     *
     * @return array
     */
    protected function reformatData(array $data)
    {
        // reformat parameters (input data)
        if (array_key_exists('parameters', $data)) {
            $data['documentation'] .= $this->engine
                ->render(
                    'OroApiBundle:ApiDoc:input.html.twig',
                    ['data' => $data['parameters']]
                );
            unset($data['parameters']);
        }

        // reformat output
        if (array_key_exists('response', $data)) {
            $data['documentation'] .= $this->engine
                ->render(
                    'OroApiBundle:ApiDoc:response.html.twig',
                    ['data' => $data['response']]
                );
            $data['parsedResponseMap'] = [];
        }

        return $data;
    }
}
