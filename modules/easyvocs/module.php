<?php

$Module = array(
    'name' => 'EasyVocs Connector Mapper',
    'variable_params' => true
);

$ViewList = array();
$ViewList['classes'] = array(
    'functions' => array( 'classes' ),
    'script' => 'classes.php',
    'params' => array( 'Identifier' ),    
    'unordered_params' => array()
);

$ViewList['object'] = array(
    'functions' => array('object'),
    'script' => 'object.php',
    'params' => array('ObjectID'),
    'unordered_params' => array()
);

$ViewList['refresh'] = array(
    'functions' => array('refresh'),
    'script' => 'refresh.php',
    'params' => array('ObjectID'),
    'unordered_params' => array()
);

$FunctionList = array();
$FunctionList['classes'] = array();
$FunctionList['object'] = array();
$FunctionList['refresh'] = array();