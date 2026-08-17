{strip}
<div class="listing stock">
	<header>
		<h1>{tr}Kit Elves{/tr}</h1>
	</header>

	<section class="body">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th>{tr}Name{/tr}</th>
					<th>{tr}Login{/tr}</th>
					<th class="text-right">{tr}Assemblies{/tr}</th>
					<th class="text-right">{tr}Movements{/tr}</th>
					<th>{tr}Stock{/tr}</th>
				</tr>
			</thead>
			<tbody>
				{foreach $elves as $elf}
				<tr>
					<td>
						<a href="{$smarty.const.CONTACT_PKG_URL}view.php?content_id={$elf.content_id}">{$elf.display_name|escape}</a>
					</td>
					<td>{$elf.linked_user_login|escape}</td>
					<td class="text-right">
						{if $elf.assembly_count}
							<a href="{$smarty.const.STOCK_PKG_URL}list_assemblies.php?user_id={$elf.user_id}">{$elf.assembly_count}</a>
						{else}
							0
						{/if}
					</td>
					<td class="text-right">
						{if $elf.movement_count}
							<a href="{$smarty.const.STOCK_PKG_URL}list_movements.php?user_id={$elf.user_id}">{$elf.movement_count}</a>
						{else}
							0
						{/if}
					</td>
					<td>
						<a href="{$smarty.const.STOCK_PKG_URL}list_stock.php?user_id={$elf.user_id}">{tr}Stock levels{/tr}</a>
					</td>
				</tr>
				{foreachelse}
				<tr><td colspan="5" class="norecords">{tr}No kit elves found.{/tr}</td></tr>
				{/foreach}
			</tbody>
		</table>
	</section>
</div>
{/strip}
