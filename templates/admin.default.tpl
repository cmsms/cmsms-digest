<script type="text/javascript">
	jQuery(function($)	{ldelim}
		$(".button").button();	
		$('#subscriptions').load("{$subscriptions_list|replace:'&amp;':'&'}&{$id}order_by=id+desc&showtemplate=false");
		$('#subscribers').load("{$subscribers_list|replace:'&amp;':'&'}&{$id}limit={$lenght}&{$id}order_by=timestamp+desc&showtemplate=false");
		$('#templates').load("{$templates_list|replace:'&amp;':'&'}&showtemplate=false");
		$('#logs').load("{$logs_list|replace:'&amp;':'&'}&{$id}limit=5&showtemplate=false");
	{rdelim});
</script>

<div style="text-align: right; margin-bottom: 7px;">
	<a href="{$options}" class="button">Options</a>
</div>

<div class="MainMenu">
	
	<div class="itemmenucontainer">
		<div class="itemoverflow">
			<a class="title-itemlink" href="{$subscriptions_list}">Subscriptions</a>
			<div id="subscriptions"></div>
		</div>
	</div>
		
	<div class="itemmenucontainer">
		<div class="itemoverflow">
			<a class="title-itemlink" href="{$templates_list}">Templates</a>
			<div id="templates"></div>
		</div>
	</div>	

	<div class="itemmenucontainer">
		<div class="itemoverflow">
			<a class="title-itemlink" href="{$subscribers_list}">Latest subscribers (view all)</a>
			<div id="subscribers"></div>
		</div>
	</div>
	
	<div class="itemmenucontainer">
		<div class="itemoverflow">
			<a class="title-itemlink" href="{$logs_list}">Logs (view all)</a>
			<div id="logs"></div>
		</div>
	</div>	
</div>