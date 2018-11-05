<div class="block">
    <label>Endpoint:</label>
    <input class="box" type="text" 
    	   name="ContentClass_easyvocsconnector_endpoint_{$class_attribute.id}" 
    	   value="{$class_attribute.content.endpoint|wash}" />
</div>

<div class="block">
    <label>Master repository:</label>
    <input class="box" type="text" 
    	   name="ContentClass_easyvocsconnector_repository_{$class_attribute.id}" 
    	   value="{$class_attribute.content.repository|wash()}" />
</div>