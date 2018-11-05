{*
    @var OCClassExtraParametersHandlerInterface $handler
    @var eZContentClass $class
    @var eZContentClassAttribute $attribute
*}
<td>
<div class="checkbox">
    <label>
        <input type="checkbox" 
        	   name="extra_handler_{$handler.identifier}[class_attribute][{$class.identifier}][{$attribute.identifier}][enable_mapper]" 
        	   value="1" {if $handler.enable_mapper|contains($attribute.identifier)}checked="checked"{/if} /> 
        	   Abilita
    </label>
</div>
</td>
