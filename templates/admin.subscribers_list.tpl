<table cellspacing="0" class="pagetable">
   <thead>
      <tr>
        <th>Subscriber</th>
		 		<th>Email</th>
		 		<th>Subscriptions</th>
        <th class="pageicon" style="width:20px"> </th>
      </tr>
   </thead>
	<tbody>
		{foreach from=$subscribers item=subscriber}
		<tr class="{cycle values="row1,row2"}" onmouseover="this.className='{cycle values="row1,row2"}hover';" onmouseout="this.className='{cycle values="row1,row2"}';">
			<td>{$subscriber.user.username}</td>
			<td>{$subscriber.user.email}</td>
			<td>
				{foreach from=$subscriber.subscriptions item=subscription name=subscriptions}
				{$subscription->getSubscription()} (<em>{$subscription->period}</em>)
				{if $smarty.foreach.subscriptions.iteration < $subscriber.subscriptions|@count}, {/if}
				{/foreach}
			</td>
			<td>
				{*}<a class="edit ui-icon ui-icon-pencil" href="{$create_link|replace:'_ITEM_ID_':$subscription->getId()}" title="Edit">Edit</a>
				<span class="delete-subscription ui-icon ui-icon-trash" href="{$delete_link|replace:'_ITEM_ID_':$subscription->getId()}" title="Delete">Delete</span>{*}
			</td>
		</tr>
		{/foreach}
	</tbody>
</table>