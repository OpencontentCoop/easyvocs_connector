<?php

use Opencontent\Opendata\Api\ClassRepository;

$module = $Params['Module'];
$classIdentifier = $Params['Identifier'];
$classRepository = new ClassRepository();

try{
	if ( $classIdentifier ){	    	    
	    $classDefinition = (array)$classRepository->load($classIdentifier);
        
        foreach ($classDefinition['fields'] as $index => $mappedField) {
            $classDefinition['fields'][$index]['isPartOfTheHash'] = EasyVocsEnvironmentSettings::isPartOfTheHash($classIdentifier, $mappedField['identifier']);            
        }

        $data = $classDefinition;

	}else{
		
        $mappedClasses = array_column(OCClassExtraParameters::fetchObjectList( 
            OCClassExtraParameters::definition(), 
            array('class_identifier'), 
            array( 
                'handler' => EasyVocsClassExtraParameters::IDENTIFIER,
                'attribute_identifier' => '*'
            ),
            null,null,false
        ), 'class_identifier');                
        
        $classes = array();        
        foreach ($mappedClasses as $classIdentifier){
            try{
                $classDefinition = (array)$classRepository->load($classIdentifier);
                $linkUrl = '/easyvocs/classes/' . $classDefinition['identifier'];
                eZURI::transformURI($linkUrl, false, 'full');
                $item = array(
                	'identifier' => $classDefinition['identifier'],
                	'name' => is_array($classDefinition['name']) ? array_shift($classDefinition['name']) : $classDefinition['name'],
                	'link' => $linkUrl
                );            
                $classes[] = $item;
            }catch(Exception $e){                
            }
        }
        $data = $classes;
	}

}catch(Exception $e){
    $data = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode( $data );
eZExecution::cleanExit();