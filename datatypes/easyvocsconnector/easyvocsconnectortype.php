<?php

use Opencontent\Opendata\Api\Values\Content;

class EasyVocsConnectorType extends eZDataType
{
    const DATA_TYPE_STRING = 'easyvocsconnector';

    const ENPOINT_FIELD = 'data_text1';

    const MASTER_REPOSITORY_FIELD = 'data_text2';

    const ENDPOINT_VARIABLE = '_easyvocsconnector_endpoint_';
    
    const MASTER_REPOSITORY_VARIABLE = '_easyvocsconnector_repository_';

    const DATA_FIELD = 'data_text';

    public static $connectionTimeout = 2;

    public static $processTimeout = 20;

    public function __construct()
    {
        $this->eZDataType(
            self::DATA_TYPE_STRING,
            ezpI18n::tr('extension/easyvocsconnector', 'EasyVocs Connector'),
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
        $newClassAttribute->setAttribute(self::MASTER_REPOSITORY_FIELD, $oldClassAttribute->attribute(self::MASTER_REPOSITORY_FIELD));
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
        $repository = $base . self::MASTER_REPOSITORY_VARIABLE . $classAttribute->attribute('id');
        if ($http->hasPostVariable($repository)) {
            $classAttribute->setAttribute(self::MASTER_REPOSITORY_FIELD, rtrim($http->postVariable($repository), '/'));
        }

        return true;
    }

    /**
     * @param eZContentClassAttribute $classAttribute
     * @return array
     */
    public function classAttributeContent($classAttribute)
    {
        $repository = $classAttribute->attribute(self::MASTER_REPOSITORY_FIELD);
        if (empty($repository)){
            $repository = eZINI::instance('easyvocs_connetor.ini')->variable('MasterRepositorySettings', 'DefaultMasterRepositoryUri');
        }
        return array(
            'endpoint' => $classAttribute->attribute(self::ENPOINT_FIELD),
            'repository' => rtrim($repository, '/')
        );
    }

    /**
     * @param eZContentClassAttribute $classAttribute
     * @param int $version     
     */
    public function storeClassAttribute($classAttribute, $version)
    {
        $classAttributeContent = $classAttribute->content();
        $class = eZContentClass::fetch((int)$classAttribute->attribute('contentclass_id'));
        $remoteRequestUrl = $classAttributeContent['repository'] . '/classtools/extra_definition/' . $class->attribute('identifier');
        $remoteData = json_decode(eZHTTPTool::getDataByURL($remoteRequestUrl), true);
        if($remoteData !== false){
            OCClassExtraParametersManager::instance($class)->sync($remoteData);
        }
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
        $endpointNode = $dom->createElement('endpoint');
        $endpointNode->appendChild($dom->createTextNode($endpoint));
        $attributeParametersNode->appendChild($endpointNode);

        $repository = $classAttribute->attribute(self::MASTER_REPOSITORY_FIELD);
        $repositoryNode = $dom->createElement('repository');
        $repositoryNode->appendChild($dom->createTextNode($repository));
        $attributeParametersNode->appendChild($repositoryNode);
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
        $endpoint = $attributeParametersNode->getElementsByTagName('endpoint')->item(0)->textContent;
        $classAttribute->setAttribute(self::ENPOINT_FIELD, $endpoint);

        $repository = $attributeParametersNode->getElementsByTagName('repository')->item(0)->textContent;
        $classAttribute->setAttribute(self::MASTER_REPOSITORY_FIELD, $repository);
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

    /**
     * @param eZContentObjectAttribute $attribute
     * @return mixed
     */
    public function objectAttributeContent($attribute)
    {
        return $attribute->attribute(self::DATA_FIELD);
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
        self::refreshData($contentObjectAttribute, $contentObject);
    }

    public static function refreshData(eZContentObjectAttribute $contentObjectAttribute, eZContentObject $contentObject)
    {
        $endPoint = $contentObjectAttribute->contentClassAttribute()->attribute(self::ENPOINT_FIELD);
        $data = self::getEasyVocsData($endPoint, $contentObject);                        
        if ($data['data']){
            $contentObjectAttribute->setAttribute(self::DATA_FIELD, $data['data']);
            $contentObjectAttribute->store();
        }

        if ($data['error']){
            $data['content'] = self::generateMappedContent($contentObject);            
            eZDebug::writeError($data['error'], __METHOD__);
        }

        return $data;
    }

    public static function refreshObject(eZContentObject $object)
    {
        $data = null;
        $dataMap = $object->dataMap();
        foreach ($dataMap as $attribute) {
            if($attribute->attribute('data_type_string') == EasyVocsConnectorType::DATA_TYPE_STRING){
                $data = EasyVocsConnectorType::refreshData($attribute, $object);
                break;
            }
        }
        if ($data === null || $data['error']){
            return isset($data['error']) ? $data['error'] : false;
        }

        return true;
    }

    private static function generateMappedContent(eZContentObject $contentObject, $asJson = true)
    {
        $request = new ezpRestRequest(
            null,
            'http-get',
            eZINI::instance('site.ini')->variable('SiteSettings', 'SiteURL')
        );
        $hostUri = $request->getHostURI();
        $apiPrefix = eZINI::instance('rest.ini')->variable('System', 'ApiPrefix');
        $requestBaseUri = $hostUri . $apiPrefix . '/opendata/v2/easyvocs';

        $apiContent = Content::createFromEzContentObject($contentObject);
        $mapperEnv = new EasyVocsEnvironmentSettings();
        $mapperEnv->request = $request;
        $mapperEnv->requestBaseUri = $requestBaseUri;
        $mapperContent = $mapperEnv->filterContent($apiContent);

        if ( $asJson ) {
            return json_encode($mapperContent);
        } else {
            return $mapperContent;
        }

    }

    public static function getEasyVocsData($endPoint, eZContentObject $contentObject)
    {
        $data = null;
        $error = null;
        try {

            $postData =  array(
                'locale' => eZLocale::currentLocaleCode(),
                'data'   => self::generateMappedContent($contentObject, false)
            );

            $postData = json_encode($postData);

            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($postData);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
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

            $info = curl_getinfo($ch);
            $headers = substr($response, 0, $info['header_size']);
            $data = substr($response, $info['header_size']);

            if ($response === false) {
                $errorCode = curl_errno($ch) * -1;
                $errorMessage = curl_error($ch);
                curl_close($ch);
                $error = "Error $errorCode: $errorMessage on endpoint $endPoint";
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        return array(
            'data' => $data,
            'error' => $error,
        );
    }

}

eZDataType::register(EasyVocsConnectorType::DATA_TYPE_STRING, 'EasyVocsConnectorType');
