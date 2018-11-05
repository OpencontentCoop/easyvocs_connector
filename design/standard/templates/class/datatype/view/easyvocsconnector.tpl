<div class="block">
    <label>Endpoint: </label> {$class_attribute.content.endpoint|wash}	
</div>
<div class="block">
    <label>Master repository: </label> {$class_attribute.content.repository|wash}    
</div>
<div class="block">    
    {def $class = fetch(content, class, hash('class_id', $class_attribute.contentclass_id))
         $mapper_params = class_extra_parameters($class.identifier, 'easyvocs')}
	<label>Attributi mappati:</label>
	<ul>
	{foreach $mapper_params.enable_mapper as $mapped}
		<li>{$mapped}</li>
	{/foreach}
	</ul>    
    <p><a href="{$class_attribute.content.repository|wash()}/classtools/extra/{$class.identifier}/easyvocs" target='_blank'>Configura attributi mappabili sul repository master</a></p>    
    <p><a href="{concat('/classtools/extra_compare/', $class.identifier, '/easyvocs?remote=', $class_attribute.content.repository|wash())|ezurl(no)}" target='_blank'>Sincronizza attributi mappabili dal repository master</a></p>    
	{undef $class $mapper_params}
</div>
