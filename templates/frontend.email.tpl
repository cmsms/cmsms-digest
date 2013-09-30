<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Updates notification</title>
<style type="text/css">
    <!-- a {literal}{font-family:Arial, Helvetica, sans-serif; color:#0c4da2; text-decoration:underline;}{/literal} -->
</style>
</head>
<body topmargin="0" marginheight="0" leftmargin="0" marginwidth="0">

<h1>Updates of {$period|date_format:"%A %e %B %Y"}</h1>

{foreach from=$modules item=module}

	{* Announces *}
		{if isset($module.announces)}
  		{foreach from=$module.announces item=announce}
				<div>
					{$announce->getTitle()}
					{$announce->getAnnouncement()}
				</div>
			{/foreach}
		{/if}
	
	{* Module content *}
 		{if isset($module.content)}
			<div>
				{$module.content}
			</div>
		{/if}

{/foreach}
</body>
</html>