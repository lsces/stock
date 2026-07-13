# Stock Package — Developer Notes

See also: `liberty/CLAUDE.md` for xref machinery underpinning this package.

## File naming convention
Entry points follow `verb_contenttype.php` pattern:
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

## StockAssembly::getList() enriched fields
Every row now includes correlated subqueries for:
- `parsed_data` — via `LibertyContent::parseDataHash()` on `lc.data`
- `part_number` — first `#SUP` xref xkey
- `klid` — first `KLID` xref xkey
- `component_count` — count of BOM quantity xrefs (SGL/PRT/SHT/VOL)
- `prebuild_count` — sum of PBLD kit counts for assembly owner (`mc.user_id = lc.user_id`)
- `child_count` — child assembly count from `stock_assembly_map`

## Movement model
Movement = pure `liberty_content` record (`content_type_guid='stockmovement'`). No `stock_movement` table.

**Direction** inferred from `reference` xref group (x_group='reference', sort_order=1):
- `REQN` = outbound (kit delivered to kitlocker)
- `PBLD` = outbound (prebuild — assembly built from components, stays with kitelf)
- `TRANS` = inbound from another elf
- `ORDER` = inbound from supplier

**PBLD workflow**: kitelf creates via `add_prebuild.php`; PBLD → REQN switch (delivery to
kitlocker) is a future action requiring user_id reassignment — not yet implemented.

**Status** = `lc.event_time` (BIGINT, Unix seconds) — `0` = placed/open, positive = received/fulfilled.
`StockMovement::markReceived()` sets it to `time()`. `isReceived()` uses `!empty()` so 0 = not received.
PBLD uses "Completed"/"In progress" labels; other types use "Received"/"Pending".

**Reference xref** (x_group='reference', sort_order=1), one row per movement:
- `item` = REQN/PBLD/TRANS/ORDER
- `xkey` = reference number/key
- `data` = free-text "from" (fallback if no contact linked); for PBLD holds optional RQ note
- `xref` = contact content_id (linked supplier/source — looked up via SCREF xkey)
- `start_date` (TIMESTAMP) = order/build date

**ASSEMBLY xref** (x_group='assembly', item='ASSEMBLY') on REQN and PBLD movements:
- `xref` = assembly content_id
- `xkey` = kit count (number of assemblies built/requested)
- `xkey_ext` = assembly title

**Items** = `quantity` xref group (x_group='quantity', sort_order=2), `multiple=1`:
- `item` = SGL/PCK/SHT/VOL
- `xref` = component content_id
- `xkey` = quantity value
- `xorder` = line sequence (managed explicitly)

**CSV format** (one movement per file): line 1 = `from(SCREF), ref, order_date(dd/mm/yy),
received_date(dd/mm/yy optional)`; lines 2+ = `component_title, quantity, [optional qty type]`.
Uploaded CSVs saved to `STOCK_IMPORT_PATH` (`storage/stock/`) as `<origname>_move_<content_id>.csv`.

## edit_movement flags
- `$isReqn` — ref_type === 'REQN'
- `$isPbld` — ref_type === 'PBLD'
- `$isBuild` — REQN or PBLD; controls assembly picker visibility, xref tab, CSV upload suppression

## Firebird SIMILAR TO gotcha
Firebird's `SIMILAR TO` has no implicit backslash escape (needs an explicit `ESCAPE`
clause) — `\.` in a pattern matches a literal backslash-then-dot, not an escaped dot. Use
`[.]` for a literal dot instead (bracket expression, no `ESCAPE` needed). This bit the
numeric-xkey filter `'[0-9]+(\.[0-9]+)?'` used across `StockMovement::explodeFromAssembly()`,
`view_component.php`, `list_stock.php`, and `add_order.php` — it silently dropped every
decimal-valued `xkey` (mainly fractional SHT quantities like `0.02`) while whole-number
SGL/PRT/VOL values happened to pass. Fixed 2026-07-13 to `'[0-9]+([.][0-9]+)?'` in all 8
occurrences. Any new `SIMILAR TO` pattern with a literal `.` (or other regex metacharacter)
must use the bracket form, not backslash-escaping.

## Kitlocker sync / import tooling
Kitlocker-specific xref items (role_id 3, `x_group='kitlocker'`), defined in
`admin/schema_inc.php`, set on both StockAssembly and StockComponent:
- `KLID` — Kitlocker ID code (e.g. `36A`) — the match key against the live site's exports
- `KLPR` — Kitlocker Price (currently unused/empty in `merg` — don't confuse with KLID)
- `KL3M` — 3-month sales count
- `KLSGL` — current stock count
- `KLG01`–`KLG99` — group tag (`stgrp` group), one per product section; name↔number map
  lives in `storage/stock/KitlockerGroups.csv` (28 groups, `G1`=General Kits...`G28`=POSTAGE)

`stock/import/` holds one-off/repeatable CSV and HTML importers (not part of the package's
core code path — ad hoc data-loading tools):
- `ImportKitlockerAssemblies.php` / `load_kitlocker_assemblies.php` — full catalogue CSV
  import (title, KLID, description, KLSGL, KL3M, group, type A/C), creates records if missing
- `ImportKitlockerStockPredict.php` / `load_kitlocker_stock_predict.php` — parses the raw
  "MERG Kitlocker - Stock predict" HTML export directly (no CSV conversion needed — table
  markup is consistent enough for DOMDocument), matches by KLID, upserts KLSGL/KL3M only.
  Unmatched codes are reported, not silently created — pass `?create=CODE:A,CODE:C` to create
  them (the export has no assembly/component column, so the type must be given explicitly).
  Does not set KLGxx group — group name is derivable from each row's enclosing `<h2>` section
  heading if this needs revisiting, since section order/names exactly match
  `KitlockerGroups.csv`, but this isn't built.

**Direction:** the CSV/HTML importers are a stopgap, not a pattern to extend — the stated
goal is to add new kitlocker items through the normal `edit_assembly.php` /
`edit_component.php` UI flow instead and retire the import scripts. Don't build more import
tooling proactively; confirm first if asked to extend this area.

## Xref display notes
**Floaticon placement** — floaticons for assembly views live in `assembly_icons_inc.tpl`,
included from `stock_simple_list_inc.tpl`. Forms in floaticon use `class="minifind"`.

**Kitlocker tab visibility** — `edit_component.php` and `view_component.php` detect kitlocker
components via KLID xref and assign `$isKitlocker`. Stash `kitlocker` and `stgrp` groups
during the normal foreach and render at the end only when `$isKitlocker` is true.

**movement edit_movement.php** filters 'reference' group in template:
`{if $xrefGroup->mXGroup neq 'reference'}` — reference is rendered directly in the form.
