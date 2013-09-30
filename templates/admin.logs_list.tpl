<table cellspacing="0" class="pagetable">
   <thead>
      <tr>
        <th>Title</th>
		 		<th>When</th>
		 		<th>Period</th>
		 		<th>Summary</th>
        <th class="pageicon" style="width:20px"> </th>
      </tr>
   </thead>
	<tbody>
		{foreach from=$logs item=log}
		<tr class="{cycle values="row1,row2"}" onmouseover="this.className='{cycle values="row1,row2"}hover';" onmouseout="this.className='{cycle values="row1,row2"}';">
			<td>{$log->title}</td>
			<td>{$log->timestamp|date_format:'%d/%m/%Y %H:%M:%S'}</td>
			<td>{$log->period}</td>
			<td>{$log->summary}</td>
			<td>
				{*}<a class="edit ui-icon ui-icon-pencil" href="{$create_link|replace:'_ITEM_ID_':$subscription->getId()}" title="Edit">Edit</a>
				<span class="delete-subscription ui-icon ui-icon-trash" href="{$delete_link|replace:'_ITEM_ID_':$subscription->getId()}" title="Delete">Delete</span>{*}
			</td>
		</tr>
		{/foreach}
	</tbody>
</table>