<?php

use Opencontent\Opendata\Api\Values\Content;

class OntopiaConnectorType extends eZDataType
{
    const DATA_TYPE_STRING = 'ontopiaconnector';

    const ENPOINT_FIELD = 'data_text1';

    const ENDPOINT_VARIABLE = '_ontopiaconnector_endpoint_';

    const DATA_FIELD = 'data_text';

    public static $connectionTimeout = 2;

    public static $processTimeout = 20;

    public function __construct()
    {
        $this->eZDataType(
            self::DATA_TYPE_STRING,
            ezpI18n::tr('extension/ontopiaconnector', 'OntoPia Connector'),
            array('serialize_supported' => true)
        );
    }

    /**
     * @param eZContentClassAttribute $oldClassAttribute
     * @param eZContentClassAttribute $newClassAttribute
     */
    function cloneClassAttribute($oldClassAttribute, $newClassAttribute)
    {
        $newClassAttribute->setAttribute(self::ENPOINT_FIELD, $oldClassAttribute->attribute(self::ENPOINT_FIELD));
    }

    /**
     * @param eZHTTPTool $http
     * @param string $base
     * @param eZContentClassAttribute $classAttribute
     *
     * @return bool
     */
    public function fetchClassAttributeHTTPInput($http, $base, $classAttribute)
    {
        $endpoint = $base . self::ENDPOINT_VARIABLE . $classAttribute->attribute('id');
        if ($http->hasPostVariable($endpoint)) {
            $classAttribute->setAttribute(self::ENPOINT_FIELD, $http->postVariable($endpoint));
        }

        return true;
    }

    /**
     * @param eZContentClassAttribute $classAttribute
     * @param DOMNode $attributeNode
     * @param DOMDocument $attributeParametersNode
     */
    public function serializeContentClassAttribute(
        $classAttribute,
        $attributeNode,
        $attributeParametersNode
    )
    {
        $dom = $attributeParametersNode->ownerDocument;

        $endpoint = $classAttribute->attribute(self::ENPOINT_FIELD);
        $endpointNode = $dom->createElement('public');
        $endpointNode->appendChild($dom->createTextNode($endpoint));
        $attributeParametersNode->appendChild($endpointNode);
    }

    /**
     * @param eZContentClassAttribute $classAttribute
     * @param DOMNode $attributeNode
     * @param DOMDocument $attributeParametersNode
     */
    public function unserializeContentClassAttribute(
        $classAttribute,
        $attributeNode,
        $attributeParametersNode
    )
    {
        $endpoint = $attributeParametersNode->getElementsByTagName('public')->item(0)->textContent;
        $classAttribute->setAttribute(self::ENPOINT_FIELD, $endpoint);
    }

    /**
     * @param eZHTTPTool $http
     * @param string $base
     * @param eZContentObjectAttribute $attribute
     *
     * @return int
     */
    public function validateObjectAttributeHTTPInput($http, $base, $attribute)
    {
        return eZInputValidator::STATE_ACCEPTED;
    }

    /**
     * @param eZContentObjectAttribute $attribute
     * @return bool
     */
    public function hasObjectAttributeContent($attribute)
    {
        return !empty($attribute->attribute(self::DATA_FIELD));
    }

    public function isInformationCollector()
    {
        return false;
    }

    public function supportsBatchInitializeObjectAttribute()
    {
        return false;
    }

    public function title($attribute, $name = null)
    {
        return null;
    }

    public function isIndexable()
    {
        return false;
    }

    public function sortKeyType()
    {
        return false;
    }

    public function metaData($attribute)
    {
        return null;
    }

    public function diff($old, $new, $options = false)
    {
        return null;
    }

    /**
     * @param eZContentObjectAttribute $contentObjectAttribute
     * @param eZContentObject $contentObject
     * @param array $publishedNodes
     */
    function onPublish($contentObjectAttribute, $contentObject, $publishedNodes)
    {
        try {
            $request = new ezpRestRequest(
                null,
                'http-get',
                eZINI::instance('site.ini')->variable('SiteSettings', 'SiteURL')
            );
            $hostUri = $request->getHostURI();
            $apiPrefix = eZINI::instance('rest.ini')->variable('System', 'ApiPrefix');
            $requestBaseUri = $hostUri . $apiPrefix . '/opendata/v2/mapper';

            $apiContent = Content::createFromEzContentObject($contentObject);
            $mapperEnv = new MapperEnvironmentSettings();
            $mapperEnv->request = $request;
            $mapperEnv->requestBaseUri = $requestBaseUri;
            $mapperContent = $mapperEnv->filterContent($apiContent);

            $endPoint = $contentObjectAttribute->contentClassAttribute()->attribute(self::ENPOINT_FIELD);

            $data = json_encode($mapperContent);

            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($data);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_URL, $endPoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connectionTimeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::$processTimeout);

            $ini = eZINI::instance();
            $proxy = $ini->hasVariable('ProxySettings', 'ProxyServer') ? $ini->variable('ProxySettings', 'ProxyServer') : false;
            if ($proxy) {
                curl_setopt($ch, CURLOPT_PROXY, $proxy);
                $userName = $ini->hasVariable('ProxySettings', 'User') ? $ini->variable('ProxySettings', 'User') : false;
                $password = $ini->hasVariable('ProxySettings', 'Password') ? $ini->variable('ProxySettings', 'Password') : false;
                if ($userName) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$userName:$password");
                }
            }

            $response = curl_exec($ch);

            if ($response === false) {
                $errorCode = curl_errno($ch) * -1;
                $errorMessage = curl_error($ch);
                curl_close($ch);
                eZDebug::writeError("Error $errorCode: $errorMessage", __METHOD__);
            }else{
                $contentObjectAttribute->setAttribute(self::DATA_FIELD, $response);
            }

        } catch (Exception $e) {
            eZDebug::writeError($e->getMessage(), __METHOD__);
        }
    }

}

eZDataType::register(OntopiaConnectorType::DATA_TYPE_STRING, 'OntopiaConnectorType');
