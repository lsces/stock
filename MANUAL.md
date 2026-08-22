# Stock Package — Reference Manual

How the package actually works today. For the history of *why* — decisions, bugs found, wrong
turns — see `CLAUDE.md`'s dated session log instead; this file only tracks current behaviour.

See also: `liberty/MANUAL.md` for the xref machinery underpinning this package.

## File naming convention

Entry points follow `verb_contenttype.php`:
- `view_assembly.php`, `edit_assembly.php`
- `view_component.php`, `edit_component.php`
- `view_movement.php`, `edit_movement.php`
- `list_assemblies.php`, `list_components.php`, `list_movements.php`
- `add_supplier.php` — specialist add page
- `add_order.php` — draft ORDER movement from shortages list; pre-populates lines with
  shortage qty, supplier autocomplete, editable qty/delete per line before creating movement
- `add_prebuild.php` — creates PBLD movement (assembly-only, BOM exploded, optional note)

`list_stock.php` — stock levels from movement xrefs. Shortages filter (`?shortages=1`) works
on both main list (level < 0) and BOM view (remaining < 0). Shortages view has floaticon icons
for Print, CSV export (`?format=csv`, part_number + qty, skips blanks), and Create Order.

## Template structure

- `stock_simple_list_inc.tpl` — assembly view header; includes `assembly_icons_inc.tpl`
  (floaticons) and `assembly_nav.tpl` (breadcrumb)
- `user_galleries.tpl` — kitelf assembly grid (3-col panels, parsed_data, counts)
- `list_assemblies.tpl` — default flat list (formerly `list_assemblies_simple.tpl`)
- `view_kitlocker.tpl` — kitlocker group gallery (formerly `stock_fixed_grid_inc.tpl`)

## Multi-user (kitelf) filtering

`list_movements.php`, `list_stock.php`, `list_assemblies.php` all accept `?user_id=X`.
- Creator names in `list_movements` are clickable filter links
- `list_assemblies.php?user_id=X` → `user_galleries.tpl` grid; else → `list_assemblies.tpl`
- Breadcrumb shows kitelf name linking to their filtered list

`list_movements.php` uses `part_content_id` (replaces separate `assembly_content_id` /
`component_content_id`); looks up `content_type_guid` and routes to the right `getList()` key.

## `StockAssembly::getList()` enriched fields

Every row includes correlated subqueries for:
- `parsed_data` — via `LibertyContent::parseDataHash()` on `lc.data`
- `part_number` — first `#SUP` xref xkey
- `klid` — first `KLID` xref xkey
- `component_count` — count of BOM quantity xrefs (SGL/PRT/SHT/VOL)
- `prebuild_count` — sum of PBLD kit counts for assembly owner (`mc.user_id = lc.user_id`)
- `child_count` — child assembly count from `stock_assembly_map` (see "BOM storage" below —
  this count is currently always 0, `stock_assembly_map` has no live rows)

## Movement model

Movement = pure `liberty_content` record (`content_type_guid='stockmovement'`). No
`stock_movement` table.

**Direction** inferred from `reference` xref group (`x_group='reference'`, `sort_order=1`):
- `REQN` = outbound (kit delivered to kitlocker)
- `PBLD` = outbound (prebuild — assembly built from components, stays with kitelf)
- `TRANS` = inbound from another elf
- `ORDER` = inbound from supplier

**PBLD workflow**: kitelf creates via `add_prebuild.php`. `edit_movement.php`'s
`fConvertToRequisition` action (button only shown when `$isPbld`) converts PBLD → REQN on
delivery to the kitlocker: changes the reference xref's `item` from `PBLD` to `REQN` and its
`xkey` from the prebuild name to the real RQ number, syncs `lc.title` to match. Deliberately
does **not** touch `user_id`/ownership — the stock-total implications of reassigning ownership
on delivery haven't been worked out, left as-is.

**Status** = `lc.event_time` (BIGINT, Unix seconds) — `0` = placed/open, positive =
received/fulfilled. `StockMovement::markReceived()` sets it to `time()`. `isReceived()` uses
`!empty()` so 0 = not received. PBLD uses "Completed"/"In progress" labels; other types use
"Received"/"Pending".

**Reference xref** (`x_group='reference'`, `sort_order=1`), one row per movement:
- `item` = REQN/PBLD/TRANS/ORDER
- `xkey` = reference number/key
- `data` = free-text "from" (fallback if no contact linked); for PBLD holds optional RQ note
- `xref` = contact content_id (linked supplier/source — looked up via SCREF xkey)
- `start_date` (TIMESTAMP) = order/build date

**`ASSEMBLY` xref** (`x_group='assembly'`, `item='ASSEMBLY'`, `multiple=1`) on REQN and PBLD
movements:
- `xref` = assembly content_id
- `xkey` = kit count (number of assemblies built/requested)
- `xkey_ext` = assembly title

**Items = `quantity` xref group** (`x_group='quantity'`, `sort_order=2`), `multiple=1`:
- `item` = SGL/PCK/SHT/VOL
- `xref` = component content_id
- `xkey` = quantity value
- `xorder` = line sequence (managed explicitly)

**Multi-assembly movements, `entry_date` batching**: `ASSEMBLY` being `multiple=1` allows more
than one assembly per movement — quantity xrefs need a back-reference to which `ASSEMBLY` xref
produced them, or two assemblies sharing a component would clobber each other's line on adjust.
Solved by repurposing `liberty_xref.entry_date` as a batch marker: every quantity line
`explodeFromAssembly()`/`fAddAssembly`'s component branch inserts gets stamped with the *same*
`entry_date` as its source `ASSEMBLY` xref (or `null`, preserved consistently, for
pre-migration rows that never got one). `rescaleFromAssembly()` and `syncComponentQuantity()`
match existing lines by component id **scoped to that entry_date**, keeping two assemblies
sharing a component correctly isolated.

**Per-assembly BOM tabs**: `edit_movement.php`/`view_movement.php` build one `{jstab}` per real
assembly (`ASSEMBLY` xref with `data='stockassembly'`) on the movement, via
`templates/view_assembly_bom_tab.tpl`, showing only that assembly's own `entry_date`-matched
quantity lines — same matching mechanism as above. Line-level edit/delete on these tabs is
gated to `p_stock_admin` (stricter than the general `allow_edit` used elsewhere); ordinary
editors only get the whole-kit-count control. Standalone components (`data='stockcomponent'`)
stay on the main "Assembly" tab, not split into their own tab.

**CSV format** (one movement per file): line 1 = `from(SCREF), ref, order_date(dd/mm/yy),
received_date(dd/mm/yy optional)`; lines 2+ = `component_title, quantity, [optional qty
type]`. Uploaded CSVs saved to `STOCK_IMPORT_PATH` (`storage/stock/`) as
`<origname>_move_<content_id>.csv`.

**Standalone component quantity sync**: a directly-added component (`fAddAssembly`'s
non-assembly branch) is represented by a single SGL quantity line, not a BOM.
`StockMovement::syncComponentQuantity()` keeps that line's `xkey` matching the `ASSEMBLY`
item's own kit-count field on adjust — `list_stock.php` reads the SGL/PRT/SHT/VOL xrefs
directly, not the `ASSEMBLY` xref's `xkey`, so letting the two drift apart silently breaks
stock totals. Only handles `item='SGL'` today — components using other quantity item types
(PRT/SHT/VOL) aren't supported by the standalone-add path.

**Tab visibility by movement type** (`edit_movement.tpl`/`view_movement.tpl`): the `assembly`
and `quantity` (flat "Items") xref-group tabs are mutually exclusive on `$isBuild` —
REQN/PBLD get the Assembly tab (+ per-assembly BOM tabs above), ORDER/TRANS get the flat Items
tab instead (no BOM to break out). The `supplier`/`stgrp`/`kitlocker` xref groups are always
hidden on a movement regardless of type — they're package-level assembly/component catalogue
metadata (dual-guid xref schema, see `liberty/MANUAL.md`), not something a movement itself has.
A movement's own supplier/contact link is the `reference` xref's `ref_contact_id` (the "From"
field), a completely separate thing from the `supplier` xref group.

**`edit_movement.php` flags**: `$isReqn` (`ref_type === 'REQN'`), `$isPbld`
(`ref_type === 'PBLD'`), `$isBuild` (REQN or PBLD — controls assembly picker visibility, xref
tab, CSV upload suppression).

## `isValid()` — checks for a real record, not just a valid-looking id

`StockMovement`/`StockAssembly`/`StockComponent::isValid()` all query `liberty_content`
directly for a matching `content_id` + `content_type_guid`, not just `verifyId($mContentId)` —
so a syntactically-valid-but-nonexistent `content_id` correctly 404s ("No X exists with the
given ID") on all six `view_*.php`/`edit_*.php` entry points, rather than rendering a
blank/broken page or silently falling into create-new mode. A `LibertyContent`-wide version of
this fix was tried and reverted — see `liberty/CLAUDE.md` for why; `contact` uses the same
per-package pattern.

## BOM storage — `liberty_xref`, not `stock_assembly_map`

BOM data lives entirely as `liberty_xref` rows under the `quantity`/`bom` group
(`sort_order=4`, items `SGL`/`PRT`/`PCK`/`SHT`/`VOL`) — same no-map-table pattern Food's own
`FoodAssembly` uses. `import/load_merg_bom.php` (the actual, currently-used BOM import tool)
writes straight to `liberty_xref`, never touches `stock_assembly_map`.

**`stock_assembly_map` itself is a real schema table, confirmed always empty in the live `merg`
DB** (checked directly, not assumed) — but still has real, live PHP code reading/writing it
(`StockAssembly::addItem()`/`getComponentMapList()`, hierarchy `connectby()` walks and
`child_count` in `StockBase.php`/`StockAssembly.php`, `StockComponent`'s reverse-lookup joins).
Every one of those queries runs fine, just against a permanently-empty table — not yet retired,
see `CLAUDE.md`'s 2026-08-16 entry for the investigation that confirmed this.

## Firebird gotchas specific to this package

**`SIMILAR TO` has no implicit backslash escape** — `\.` in a pattern matches a literal
backslash-then-dot, not an escaped dot (needs an explicit `ESCAPE` clause to work that way).
Use `[.]` for a literal dot instead (bracket expression, no `ESCAPE` needed). Any numeric-xkey
filter pattern in this package (`explodeFromAssembly()`, `view_component.php`,
`list_stock.php`, `add_order.php`) should read `'[0-9]+([.][0-9]+)?'`, not
`'[0-9]+(\.[0-9]+)?'` — the latter silently drops every decimal-valued `xkey` (fractional SHT
quantities) while passing whole-number values.

**`{smartlink isort="..."}` needs the bare field name** — `function.smartlink.php` appends
`_asc`/`_desc` itself to build the toggle link; passing a pre-suffixed value (e.g.
`isort="created_desc"`) produces `sort_mode=created_desc_asc`, and `BitDb::
convertSortmodeOneItem()` only strips the *trailing* `_asc`/`_desc`, leaving an unknown column
name and a Firebird error. Any sortable `list_movements.tpl` column must use the bare name.

**Single-input forms need an explicit hidden action field, not a submit-button name** —
`edit_movement.php`-style handlers that branch on `!empty($_REQUEST['fSomeAction'])` where
`fSomeAction` is a submit button's `name` break silently on Enter-to-submit (Firefox omits the
button's name/value when the form has only one text/number input and Enter triggers the
submit, not a click). Carry the action flag as `<input type="hidden" name="fSomeAction"
value="1" />` instead — used by the per-row adjust form (`view_xref_assembly_item.tpl`) and the
"Add item" form (`view_xref_assembly_group.tpl`). Any new single-input stock form should follow
this from the start.

## Kitlocker items and sync tooling

Kitlocker-specific xref items (`role_id 3`, `x_group='kitlocker'`), defined in
`admin/schema_inc.php`, set on both `StockAssembly` and `StockComponent`:
- `KLID` — Kitlocker ID code (e.g. `36A`) — the match key against the live site's exports
- `KLPR` — Kitlocker Price (currently unused/empty in `merg` — don't confuse with KLID)
- `KL3M` — 3-month sales count
- `KLSGL` — current stock count
- `KLG01`–`KLG99` — group tag (`stgrp` group), one per product section; name↔number map
  lives in `storage/stock/KitlockerGroups.csv` (28 groups, `G1`=General Kits...`G28`=POSTAGE)

`stock/import/` holds one-off/repeatable CSV and HTML importers (ad hoc data-loading tools, not
part of the package's core code path):
- `ImportKitlockerAssemblies.php` / `load_kitlocker_assemblies.php` — full catalogue CSV
  import (title, KLID, description, KLSGL, KL3M, group, type A/C), creates records if missing
- `ImportKitlockerStockPredict.php` / `load_kitlocker_stock_predict.php` — parses the raw
  "MERG Kitlocker - Stock predict" HTML export directly (DOMDocument, no CSV conversion
  needed), matches by KLID, upserts KLSGL/KL3M only. Unmatched codes are reported, not
  silently created — pass `?create=CODE:A,CODE:C` to create them (the export has no
  assembly/component column, so type must be given explicitly). Doesn't set `KLGxx` group —
  derivable from each row's enclosing `<h2>` section heading if this ever needs revisiting
  (section order/names match `KitlockerGroups.csv`), but not built. Reachable from the Stock
  admin menu ("Sync Kitlocker Stock Predict") — the page has a browser upload form (`html_file`)
  writing straight to `storage/stock/KitlockerStockPredict.html`, no manual file copy needed.
  Only processes on a fresh upload or an explicit `?create=` retry, never on a bare page visit.

**Not a pattern to extend**: the CSV/HTML importers are a stopgap — the stated direction is
adding new kitlocker items through the normal `edit_assembly.php`/`edit_component.php` UI flow
instead and retiring the import scripts. Don't build more import tooling proactively.

## Xref display notes

**Floaticon placement** — floaticons for assembly views live in `assembly_icons_inc.tpl`,
included from `stock_simple_list_inc.tpl`. Forms in floaticon use `class="minifind"`.

**Kitlocker tab visibility** — `edit_component.php` and `view_component.php` detect kitlocker
components via KLID xref and assign `$isKitlocker`. Stash `kitlocker` and `stgrp` groups
during the normal foreach and render at the end only when `$isKitlocker` is true.

**`edit_movement.php`** filters the 'reference' group in template:
`{if $xrefGroup->mXGroup neq 'reference'}` — reference is rendered directly in the form.
