{strip}
<div class="listing stock">
	<header>
		<div class="floaticon">
			{if $gBitUser->hasPermission('p_stock_create')}
				<a class="btn btn-default btn-sm" href="{$smarty.const.STOCK_PKG_URL}edit_movement.php">{tr}Add Movement{/tr}</a>
			{/if}
		</div>
		<h1>{tr}Movements{/tr}</h1>
	</header>

	<section class="body">

		{* Filter bar *}
		{form ipackage="stock" ifile="list_movements.php" method="get"}
			<div class="form-inline" style="margin-bottom:1em">
				<div class="form-group">
					<select name="direction" class="form-control input-sm">
						<option value="">{tr}All directions{/tr}</option>
						<option value="I"{if $filterDir eq 'I'} selected="selected"{/if}>{tr}IN{/tr}</option>
						<option value="O"{if $filterDir eq 'O'} selected="selected"{/if}>{tr}OUT{/tr}</option>
					</select>
				</div>
				<div class="form-group">
					<select name="status" class="form-control input-sm">
						<option value="">{tr}All statuses{/tr}</option>
						{foreach ['draft'=>'Draft','pending'=>'Pending','complete'=>'Complete','cancelled'=>'Cancelled'] as $val=>$label}
							<option value="{$val}"{if $filterStatus eq $val} selected="selected"{/if}>{tr}{$label}{/tr}</option>
						{/foreach}
					</select>
				</div>
				<button type="submit" class="btn btn-default btn-sm">{tr}Filter{/tr}</button>
			</div>
		{/form}

		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>{smartlink ititle="Title" isort="title"}</th>
					<th>{tr}Direction{/tr}</th>
					<th>{tr}Status{/tr}</th>
					<th>{smartlink ititle="Date" isort="last_modified_desc"}</th>
					<th>{tr}Creator{/tr}</th>
					{if $gBitUser->hasPermission('p_stock_create')}<th></th>{/if}
				</tr>
			</thead>
			<tbody>
				{foreach $movementList as $mov}
					<tr>
						<td><a href="{$mov.display_url|escape}">{$mov.title|escape}</a></td>
						<td>{if $mov.direction eq 'I'}{tr}IN{/tr}{else}{tr}OUT{/tr}{/if}</td>
						<td>{$mov.status|escape|capitalize}</td>
						<td>{$mov.last_modified|bit_short_date}</td>
						<td>{$mov.real_name|default:$mov.login|escape}</td>
						{if $gBitUser->hasPermission('p_stock_create')}
							<td>
								<a class="btn btn-xs btn-default"
									href="{$smarty.const.STOCK_PKG_URL}edit_movement.php?movement_id={$mov.movement_id}">{tr}Edit{/tr}</a>
							</td>
						{/if}
					</tr>
				{foreachelse}
					<tr><td colspan="6" class="norecords">{tr}No movements found.{/tr}</td></tr>
				{/foreach}
			</tbody>
		</table>

		<nav>
			{pagination}
		</nav>

	</section>
</div>
{/strip}
