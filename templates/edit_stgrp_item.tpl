{strip}
<div class="edit stock">
	<header>
		<div class="floaticon">
		</div>
		<h1>{tr}Edit{/tr}: {$stgrpItem.cross_ref_title|escape}</h1>
		<small><a href="{$smarty.const.STOCK_PKG_URL}view_kitlocker.php">&lsaquo; {tr}Kitlocker{/tr}</a></small>
	</header>

	<section class="body">
		{formfeedback error=$errors}
		{form}
			<input type="hidden" name="item" value="{$stgrpItem.item|escape}"/>

			<div class="form-group">
				{formlabel label="Description" for="editliberty"}
				{forminput}
					{textarea edit=$stgrpItem.data name='data' rows=10}
				{/forminput}
			</div>

			<div class="form-group submit">
				<input type="submit" class="btn btn-default" name="fCancel" value="{tr}Cancel{/tr}"/>
				<input type="submit" class="btn btn-primary" name="fSave"   value="{tr}Save{/tr}"/>
			</div>
		{/form}
	</section>
</div>
{/strip}
