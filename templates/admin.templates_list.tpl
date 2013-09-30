<script type="text/javascript">
	jQuery(function($)	{ldelim}
		
		{literal}
		$( "#dialog-delete-template" ).dialog({
			resizable: false,
			height:140,
			modal: true,
			autoOpen: false,
			buttons: {
				"Delete template": function() {
					location.href = url;
				},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			}
		});
		{/literal}
		
		$('.delete-template').click(function(){ldelim}
			url = $(this).attr('href');
			$("#dialog-delete-template").dialog('open');
		{rdelim});
	{rdelim});
</script>

<table cellspacing="0" class="pagetable">
   <thead>
      <tr>
        <th>Template</th>
        <th class="pageicon" style="width:20px"> </th>
      </tr>
   </thead>
	<tbody>
		{foreach from=$templates item=template}
		<tr class="{cycle values="row1,row2"}" onmouseover="this.className='{cycle values="row1,row2"}hover';" onmouseout="this.className='{cycle values="row1,row2"}';">
			<td>{$template}</td>
			<td>
				<a class="edit ui-icon ui-icon-pencil" href="{$create_template|replace:'_TEMPLATE_':$template}" title="Edit">Edit</a>
				<span class="delete-template ui-icon ui-icon-trash" href="{$delete_template|replace:'_TEMPLATE_':$template}" title="Delete">Delete</span>
			</td>
		</tr>
		{/foreach}
	</tbody>
</table>

<a class="button" href="{$create_template|replace:'_TEMPLATE_':''}">Create a new template</a>

<div id="dialog-delete-template" title="Delete this template?">
	<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>This template will be permanently deleted and cannot be recovered. Are you sure?</p>
</div>