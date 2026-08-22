# Stock Package — Developer Notes

Dated history: decisions, bugs found, why things are the way they are, open follow-ups. For the
current architecture/reference — schema, mechanisms, gotchas — see `MANUAL.md` instead; this
file only tracks how it got there.

See also: `liberty/CLAUDE.md` for xref machinery underpinning this package.

## 2026-08-10 — multi-assembly movements, isValid() fix, two gotchas found

**Multi-assembly movements fixed via `entry_date`**: `ASSEMBLY` being `multiple=1` means the
data model always allowed more than one assembly per movement, and `explodeFromAssembly()`'s
own docstring anticipated it — but originally quantity xrefs carried no back-reference to which
`ASSEMBLY` xref produced them, so two assemblies sharing a component clobbered each other's line
on adjust. Fixed by repurposing `liberty_xref.entry_date` (a genuinely dead column sitewide
until this — see `liberty/CLAUDE.md`) as a batch marker. Verified live with two real assemblies
sharing a component on the same movement. See `MANUAL.md`'s Movement model section for the
settled mechanism.

**`isValid()` real bug fixed**: before this, a syntactically-valid-but-nonexistent `content_id`
read as "valid" through the base `LibertyContent::isValid()` — `view_*.php` rendered
blank/broken pages, `edit_*.php` silently fell into create-new mode. Given
`StockMovement`/`StockAssembly`/`StockComponent` a real DB-querying override, all six
`view_*.php`/`edit_*.php` entry points now 404 consistently. Same fix applied to `contact`'s
classes the same day; a `LibertyContent`-wide version was tried and reverted, see
`liberty/CLAUDE.md`. **Side effect, found 2026-08-11**: this same change exposed a real kernel
destructor bug, crashing live on srv10 until fixed — see `kernel/CLAUDE.md`'s "APCu object
cache" entry.

**Smartlink isort gotcha**: `list_movements.tpl`'s Date column was wrongly given
`isort="created_desc"` (pre-suffixed), building a broken `sort_mode` and a Firebird "Column
unknown" error on click. Fixed to `isort="created"`. See `MANUAL.md`'s Firebird gotchas section
for the underlying rule.

**Form action-dispatch gotcha**: found via the "Adjust quantities" inline Set/Save control —
pressing Enter on a single-input form silently no-opped instead of submitting (Firefox omits a
submit button's name/value when Enter triggers submission on a lone-input form). Fixed by
carrying the action flag as an explicit hidden input instead. See `MANUAL.md` for the pattern to
follow on any new single-input stock form.

## 2026-07-13 — Firebird `SIMILAR TO` silently dropped decimal quantities

`\.` in a `SIMILAR TO` pattern matches a literal backslash-then-dot, not an escaped dot —
Firebird has no implicit backslash escape. This silently dropped every decimal-valued `xkey`
(mainly fractional SHT quantities like `0.02`) from the numeric-xkey filter used across
`StockMovement::explodeFromAssembly()`, `view_component.php`, `list_stock.php`, and
`add_order.php`, while whole-number SGL/PRT/VOL values happened to pass — silent enough that it
went unnoticed until specifically checked. Fixed to the bracket form (`[.]`) in all 8
occurrences. See `MANUAL.md`'s Firebird gotchas section.

## 2026-08-16 — `stock_assembly_map` confirmed dead, not just "possibly"

Checked properly against the real `merg` database (the live stock DB — `rdmcloud` doesn't even
have this table, wrong DB to check against) rather than reasoning from code alone:

- `SELECT COUNT(*) FROM stock_assembly_map` → **0 rows**.
- Real BOM data: 122 `stockassembly` content records, 359 real BOM-line `liberty_xref` rows
  under `SGL`/`PRT`/`PCK`/`SHT`/`VOL` — confirming the xref route is genuinely where BOM data
  lives, exactly the pattern Food's own `FoodAssembly` uses (no map table, ingredient rows are
  xref rows).
- `import/load_merg_bom.php` — the actual, currently-used BOM import tool — writes straight to
  `liberty_xref`, never touches `stock_assembly_map` at all.
- Side note while there: `SGL`/`PRT`/`SHT`/`VOL` are registered `multiple=0` in
  `schema_inc.php` but the real importer writes several `xorder`-incrementing rows per item per
  assembly anyway (359 rows across 122 assemblies is obviously >1 row/type on average) — the
  `multiple` flag on this group doesn't reflect actual usage, a separate small doc/schema
  inconsistency, not chased further.

So `stock_assembly_map` is a schema table with a real amount of live PHP code still
reading/writing it (`StockAssembly::addItem()`/`getComponentMapList()`, hierarchy
`connectby()` walks and `child_count` in `StockBase.php`/`StockAssembly.php`,
`StockComponent`'s own reverse-lookup joins) that all silently operates on nothing — every one
of those queries runs fine, just against a permanently-empty table. Not yet touched (retiring
active, referenced code is a bigger, separate job), but the "is it load-bearing" question is
now answered: no.

## Pending

- Retiring the dead `stock_assembly_map` code paths (see above) — bigger job, not scoped.
- Kitlocker CSV/HTML import scripts are a stopgap (see `MANUAL.md`) — direction is moving new
  kitlocker items through the normal edit UI instead and retiring the importers. Don't build
  more import tooling proactively.
