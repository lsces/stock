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

**PBLD workflow**: kitelf creates via `add_prebuild.php`. PBLD → REQN switch (delivery to
kitlocker) is now built: `edit_movement.php`'s `fConvertToRequisition` action (button only
shown when `$isPbld`) changes the reference xref's `item` from `PBLD` to `REQN` and its `xkey`
from the prebuild name to the real RQ number, and syncs `lc.title` to match. Deliberately does
**not** touch `user_id`/ownership — the stock-total implications of reassigning ownership on
delivery haven't been worked out, so it's left as-is for now.

**Status** = `lc.event_time` (BIGINT, Unix seconds) — `0` = placed/open, positive = received/fulfilled.
`StockMovement::markReceived()` sets it to `time()`. `isReceived()` uses `!empty()` so 0 = not received.
PBLD uses "Completed"/"In progress" labels; other types use "Received"/"Pending".

**Reference xref** (x_group='reference', sort_order=1), one row per movement:
- `item` = REQN/PBLD/TRANS/ORDER
- `xkey` = reference number/key
- `data` = free-text "from" (fallback if no contact linked); for PBLD holds optional RQ note
- `xref` = contact content_id (linked supplier/source — looked up via SCREF xkey)
- `start_date` (TIMESTAMP) = order/build date

**ASSEMBLY xref** (x_group='assembly', item='ASSEMBLY', `multiple=1`) on REQN and PBLD movements:
- `xref` = assembly content_id
- `xkey` = kit count (number of assemblies built/requested)
- `xkey_ext` = assembly title

**Items** = `quantity` xref group (x_group='quantity', sort_order=2), `multiple=1`:
- `item` = SGL/PCK/SHT/VOL
- `xref` = component content_id
- `xkey` = quantity value
- `xorder` = line sequence (managed explicitly)

**Multi-assembly movements — solved via `entry_date` (2026-08-10)**: `ASSEMBLY` being
`multiple=1` means the data model allows more than one assembly per movement, and
`explodeFromAssembly()`'s docstring anticipated it ("safe to call multiple times, e.g. for
multi-assembly requisitions"). Originally quantity xrefs carried no back-reference to which
`ASSEMBLY` xref produced them, so two assemblies sharing a component would clobber each
other's line on adjust. Fixed by repurposing `liberty_xref.entry_date` (a genuinely dead
column sitewide until this — see `liberty/CLAUDE.md`) as a batch marker: every quantity line
`explodeFromAssembly()`/`fAddAssembly`'s component branch inserts gets stamped with the
*same* `entry_date` as its source `ASSEMBLY` xref (or `null`, preserved consistently, for
pre-migration rows that never got one). `rescaleFromAssembly()` and `syncComponentQuantity()`
then match existing lines by component id **scoped to that entry_date**, so two assemblies
sharing a component keep separate, correctly-isolated lines. Verified live with two real
assemblies sharing a component on the same movement — see session log below for the test.

**Per-assembly BOM tabs**: `edit_movement.php`/`view_movement.php` build one `{jstab}` per
real assembly (`ASSEMBLY` xref with `data='stockassembly'`) on the movement, via
`templates/view_assembly_bom_tab.tpl`, showing only that assembly's own `entry_date`-matched
quantity lines — same matching mechanism as above. Line-level edit/delete on these tabs is
gated to `p_stock_admin` (stricter than the general `allow_edit` used elsewhere); ordinary
editors only get the whole-kit-count control. Standalone components (`data='stockcomponent'`)
stay on the main "Assembly" tab, not split into their own tab — see `StockMovement::
syncComponentQuantity()` below.

**CSV format** (one movement per file): line 1 = `from(SCREF), ref, order_date(dd/mm/yy),
received_date(dd/mm/yy optional)`; lines 2+ = `component_title, quantity, [optional qty type]`.
Uploaded CSVs saved to `STOCK_IMPORT_PATH` (`storage/stock/`) as `<origname>_move_<content_id>.csv`.

**Standalone component quantity sync**: a directly-added component (`fAddAssembly`'s
non-assembly branch) is represented by a single SGL quantity line, not a BOM. `StockMovement::
syncComponentQuantity()` keeps that line's `xkey` matching the `ASSEMBLY`-item's own kit-count
field on adjust — stock level calculations (`list_stock.php`) read the SGL/PRT/SHT/VOL xrefs
directly, not the `ASSEMBLY` xref's `xkey`, so letting the two drift apart silently broke
stock totals until this was added. Only handles `item='SGL'` — fine today since every
kitlocker component is sold singly; components using other quantity item types (PRT/SHT/VOL)
aren't supported by the standalone-add path at all.

**Tab visibility by movement type** (`edit_movement.tpl`/`view_movement.tpl`): the `assembly`
and `quantity` (flat "Items") xref-group tabs are mutually exclusive on `$isBuild` — REQN/PBLD
get the Assembly tab (+ per-assembly BOM tabs above), ORDER/TRANS get the flat Items tab
instead (no BOM to break out). The `supplier`/`stgrp`/`kitlocker` xref groups are always
hidden on a movement regardless of type — they're package-level assembly/component catalogue
metadata (dual-guid xref schema, see `liberty/CLAUDE.md`), not something a movement itself
has. A movement's own supplier/contact link is the `reference` xref's `ref_contact_id` (the
"From" field), a completely separate thing from the `supplier` xref group.

**`isValid()` — checks for a real record, not just a valid-looking id**:
`StockMovement`/`StockAssembly`/`StockComponent::isValid()` all query `liberty_content`
directly for a matching `content_id` + `content_type_guid`, not just `verifyId($mContentId)`.
Before this fix, a syntactically-valid-but-nonexistent `content_id` (e.g. `?content_id=99999999`)
read as "valid" — `view_*.php` rendered blank/broken pages instead of 404ing, and
`edit_*.php` silently fell into create-new mode instead of erroring. All six `view_*.php`/
`edit_*.php` entry points now show a consistent "No X exists with the given ID" 404. A
`LibertyContent`-wide version of this fix was tried and reverted — see `liberty/CLAUDE.md`.

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

## Smartlink isort gotcha (2026-08-10)
`{smartlink isort="..."}` (`list_movements.tpl`'s column headers) must be passed the **bare**
field name — `function.smartlink.php` appends `_asc`/`_desc` itself to build the toggle link.
The Date column was wrongly given `isort="created_desc"` (pre-suffixed), so clicking it built
`sort_mode=created_desc_asc`/`created_desc_desc` — `BitDb::convertSortmodeOneItem()` only
strips the *trailing* `_asc`/`_desc`, leaving `created_desc` as the "column name", which
doesn't exist → Firebird `Column unknown CREATED_DESC`. Fixed to `isort="created"`, matching
the other three (working) columns. Any new sortable column here must follow the same rule.

## Form action-dispatch gotcha (2026-08-10)
`edit_movement.php` branches on `!empty($_REQUEST['fSomeAction'])` to pick which handler runs,
where `fSomeAction` was the `name` of the clicked `<button type="submit" name="fSomeAction">`.
This breaks silently on forms with a single number/text input — pressing Enter to submit
(rather than clicking the button) makes Firefox omit the button's name/value entirely, so the
`elseif` chain matches nothing and the page just re-renders as if freshly loaded, with no
error. Found via the "Adjust quantities" inline Set/Save control. Fixed by carrying the
action flag as an explicit `<input type="hidden" name="fSomeAction" value="1" />` instead —
applies to both the per-row adjust form (`view_xref_assembly_item.tpl`) and the "Add item"
form (`view_xref_assembly_group.tpl`). Any new single-input stock form should follow this
pattern from the start.

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
  **How to actually run it (2026-08-10)**: reachable from the Stock admin menu ("Sync
  Kitlocker Stock Predict"). The page itself now has a browser upload form (`html_file`) that
  writes straight to `storage/stock/KitlockerStockPredict.html` — no manual file copy needed.
  Only actually processes on a fresh upload or an explicit `?create=` retry, never on a bare
  page visit (a plain GET used to silently re-run against whatever stale file was already on
  disk). Unmatched codes list with "Add as Assembly"/"Add as Component" buttons (plain
  `?create=CODE:A`/`:C` links reusing the already-saved file) instead of requiring the query
  string to be typed by hand.

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
