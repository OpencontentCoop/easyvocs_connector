<?php
/** @var eZModule $module */
$module = $Params['Module'];
$objectID = $Params['ObjectID'];

$isJsonLdRequest = false;
$accept = eZSys::serverVariable('HTTP_ACCEPT');
if (strpos($accept, 'ld+json')){
    $isJsonLdRequest = true;
}
$isJsonDebugRequest = isset($_GET['ContentType']) && $_GET['ContentType'] == 'json';
if ($objectID){
    $object = eZContentObject::fetch((int)$objectID);
    if ( $object instanceof eZContentObject ){
        if ($isJsonLdRequest || $isJsonDebugRequest){
            $dataMap = $object->dataMap();
            foreach ($dataMap as $attribute) {
                if($attribute->attribute('data_type_string') == EasyVocsConnectorType::DATA_TYPE_STRING){
                    if (!$attribute->hasContent()){
                        EasyVocsConnectorType::refreshData($attribute, $object);
                    }
                    $jsonLdData = $attribute->content();
                    if ($isJsonDebugRequest){
                        header('Content-Type: application/json');
                    }else{
                        header('Content-Type: application/ld+json');
                    }
                    echo $jsonLdData;
                    eZExecution::cleanExit();
                }
            }
        }else{
            $node = $object->attribute( 'main_node' );
            if ( $node instanceof eZContentObjectTreeNode ){
                $redirect = $node->attribute( 'url_alias' );        
                return $module->redirectTo($redirect);
            }
        }
    }
}

return $module->redirectTo('/');

