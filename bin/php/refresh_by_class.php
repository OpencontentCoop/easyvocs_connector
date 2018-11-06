<?php
require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance(array(
    'description' => ( "Reindicizza\n\n" ),
    'use-session' => false,
    'use-modules' => true,
    'use-extensions' => true
));

$script->startup();

$options = $script->getOptions('[class:]',
    '',
    array('class' => 'Identificatore della classe')
);
$script->initialize();
$script->setUseDebugAccumulators(true);

try {
    if (isset($options['class'])) {
        $classIdentifier = $options['class'];
    } else {
        throw new Exception("Specificare la classe");
    }

    $class = eZContentClass::fetchByIdentifier($classIdentifier);
    if (!$class instanceof eZContentClass) {
        throw new Exception("Classe $classIdentifier non trovata");
    }

    $ids = array_column(eZPersistentObject::fetchObjectList(eZContentObject::definition(),
        array('id'),
        array('contentclass_id' => $class->attribute('id')),
        null,
        null,
        false), 'id');
    
    if (count($ids) > 0) {
        $count = count($ids);
        $cli->output("Refresh $count objects");
        

        foreach ($ids as $id) {
            $cli->output("#$id " , false);
            $object = eZContentObject::fetch($id);
            $refreshStatus = EasyVocsConnectorType::refreshObject($object);
            if ($refreshStatus === true){
                $cli->output("OK");
            }else{
                $cli->error("KO $refreshStatus");
            }
            eZContentObject::clearCache();
        }        
    }

    $cli->output('');


    $script->shutdown();
} catch (Exception $e) {
    $errCode = $e->getCode();
    $errCode = $errCode != 0 ? $errCode : 1; // If an error has occured, script must terminate with a status other than 0
    $script->shutdown($errCode, $e->getMessage());
}
