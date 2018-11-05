<?php
/** @var eZModule $module */
$module = $Params['Module'];
$objectID = $Params['ObjectID'];

if ($objectID){
    $object = eZContentObject::fetch((int)$objectID);
    if ( $object instanceof eZContentObject ){
        
        $dataMap = $object->dataMap();
        foreach ($dataMap as $attribute) {
            if($attribute->attribute('data_type_string') == EasyVocsConnectorType::DATA_TYPE_STRING){
                $data = EasyVocsConnectorType::refreshData($attribute, $object);
            }
        }

        if ($data['error']){            
            echo '<h1>Error</h1>';
            echo '<p>' . $data['error'] . '</p>';
            echo '<pre>' . $data['content'] . '</pre>';
            eZExecution::cleanExit();
        }
    
        return $module->redirectTo('/easyvocs/object/' . $objectID . '?ContentType=json');
    }
}

echo "<h1>Error: object $objectID not found</h1>";
eZExecution::cleanExit();

