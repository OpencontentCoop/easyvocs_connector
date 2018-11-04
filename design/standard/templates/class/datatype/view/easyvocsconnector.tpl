<div class="block">
    <label>Endpoint: </label> {$class_attribute.data_text1|wash}
	{def $class = fetch(content, class, hash('class_id', $class_attribute.contentclass_id))
		 $mapper_params = class_extra_parameters($class.identifier, 'mapper')}
    <p>
    	<strong>Attributi mappati:</strong>
    	<ul>
    	{foreach $mapper_params.enable_mapper as $mapped}
    		<li>{$mapped}</li>
    	{/foreach}
    	</ul>
    	<a href="{concat('classtools/extra/',$class.identifier,'/mapper')|ezurl(no)}">Configura attributi mappabili</a>
    	
    </p>
	{undef $class}
</div>
