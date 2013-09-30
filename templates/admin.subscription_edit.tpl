<script src="/modules/Digest/js/double.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript">
	jQuery(function($)	{ldelim}

		$(".button").button();
		
	  var templates = {ldelim}
		{foreach from=$modules item=module key=module_name}
			"{$module.name}": {ldelim}
				"key": '{$module_name}',
				"values": {ldelim}
					{foreach from=$module.templates item=template}
					"{$template}": '{$template}',
					{/foreach}
				{rdelim}
			{rdelim},
		{/foreach}
		{rdelim};
		
		var options = {ldelim}
		preselectFirst: '{$selected_module}',
		preselectSecond: '{$selected_template}'
		{rdelim};

		$('#{$id}module_name').doubleSelect('{$id}template', templates, options);		
	{rdelim});
</script>

{$form->render()}