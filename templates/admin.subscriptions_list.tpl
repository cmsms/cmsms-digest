<script type="text/javascript">
	jQuery(function($)	{ldelim}
		$(".button").button();	
		
		{literal}
		$( "#dialog-delete-subscription" ).dialog({
			resizable: false,
			height:140,
			modal: true,
			autoOpen: false,
			buttons: {
				"Delete subscription": function() {
					location.href = url;
				},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			}
		});
		{/literal}
		
		$('.delete-subscription').click(function(){ldelim}
			url = $(this).attr('href');
			$("#dialog-delete-subscription").dialog('open');
		{rdelim});
	{rdelim});
</script>

<table cellspacing="0" class="pagetable">
   <thead>
      <tr>
        <th>Subscription</th>
		 		<th>Module</th>
		 		<th>Parameters</th>
		 		<th>Template</th>
        <th class="pageicon" style="width:20px"> </th>
      </tr>
   </thead>
	<tbody>
		{foreach from=$subscriptions item=subscription}
		<tr class="{cycle values="row1,row2"}" onmouseover="this.className='{cycle values="row1,row2"}hover';" onmouseout="this.className='{cycle values="row1,row2"}';">
			<td>{$subscription->title}</td>
			<td>{$subscription->module_name}</td>
			<td>{$subscription->params}</td>
			<td>{$subscription->template}</td>
			<td>
				<a class="edit ui-icon ui-icon-pencil" href="{$create_link|replace:'_ITEM_ID_':$subscription->getId()}" title="Edit">Edit</a>
				<span class="delete-subscription ui-icon ui-icon-trash" href="{$delete_link|replace:'_ITEM_ID_':$subscription->getId()}" title="Delete">Delete</span>
			</td>
		</tr>
		{/foreach}
	</tbody>
</table>

<a class="button" href="{$create_link|replace:'_ITEM_ID_':''}">Create a new subscription</a>

<div id="dialog-delete-subscription" title="Delete this item?">
	<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>This item will be permanently deleted and cannot be recovered. Are you sure?</p>
</div>