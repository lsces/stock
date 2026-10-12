# Stock — User Manual

How to use the stock system: components, kits (assemblies), the movements that change stock levels,
and keeping stock counts in step with Kitlocker. For how it works inside (classes, data shapes,
Firebird gotchas), see [`DEVELOPER.md`](DEVELOPER.md).

## The idea

Stock keeps track of what a kit-builder (a "kitelf") has in hand and what it takes to build a kit.

- A **component** is a single part: a resistor, a PCB, a bag of screws.
- An **assembly** is a kit. Its **parts list** (bill of materials, BOM) says which components go in
  it and how many of each.
- A **movement** is anything that changes stock: an order arriving, a kit being built, a kit being
  delivered to the Kitlocker.

Stock levels are never typed in by hand. They are worked out from the movements, so the level you
see is always the sum of what came in less what went out, and you can see which movement caused it.

## The menu

Stock appears in the main menu as a set of icons. What you see depends on your permissions.

| Icon / name | Takes you to |
|---|---|
| Kitlocker | The Kitlocker groups (see below) |
| List Assemblies | Every kit |
| Kit Elves | Each kitelf, with their kit and movement counts (needs permission to view contacts) |
| List Components | Every component |
| List Movements | Every movement, newest first |
| Stock Levels | Current stock of every component |
| Create a Component / Create an Assembly / Add Movement / Create Prebuild | Forms to add new records (needs permission to create) |

## Components

**List Components** shows every component with a search box (by name) and a **Hide kitlocker**
tickbox, which drops the Kitlocker-only items so you only see real parts. Click a column heading to
sort. If you can edit, each row has an edit icon.

Click a component's name to see its page. The **Stock** tab shows its level for each quantity type,
and **Stock history** lists every movement that touched it.

**Create a Component** (or the edit icon on an existing one): give it a name and description, then
use the tabs for its other details such as supplier and part number. Save with **Save Component**.
Deleting a component also removes it from every kit it was part of, so you are asked to confirm.

### Quantity types

A component is counted in one of four ways. You pick the type when you add it to a parts list or a
movement.

| Type | Use for |
|---|---|
| Single unit | Things counted one by one |
| Part | Things bought as a pack or length where you use part of one |
| Sheet | Sheet material |
| Volume | Liquids, glue, paint |

## Assemblies (kits)

**List Assemblies** shows every kit. With the kitelf filter on (see below) it shows one person's kits
as a grid of pictures instead.

Click a kit to see it. **Create an Assembly** (or edit) has tabs for the kit's details; save with
**Save Assembly**.

### The parts list

The kit's parts list is the list of components and the quantity of each that go into one kit.

- **Adding one part at a time:** use the edit tabs on the assembly.
- **Loading a whole list:** on the edit page use **Upload BOM** with a CSV whose columns are
  *Component, Order, Quantity, Size (SGL/PRT/SHT/VOL), Ref designators*. The page reports how many
  lines were loaded and how many skipped.
- **Order:** the Order number sets the line's position and group. The thousands digit is the group
  (so `1005` is group 1, position 5), and groups show as headings on the printed list.
- **Printing:** the **Print Parts List** icon on the kit's page opens a print-friendly list:
  position, component, description, quantity and reference designators. The print dialog opens
  automatically.
- **Re-ordering:** the **Component Order** icon (editors only) lets you change the order numbers and
  save them. This page also appears after you add a component to the kit.
- **Other icons on a kit's page:** **View Stock** shows the stock levels for just this kit's parts,
  **View Movements** lists the movements that involve it.

## Movements

A movement is one event that changes stock. Open **List Movements** to see them. You can filter by
type and search by reference; the columns show the reference, the type, when it was ordered, when
it was received, who created it and when.

There are four types:

| Type | Direction | What it is |
|---|---|---|
| **Order** | In | Parts ordered from a supplier |
| **Transfer** | In | Parts received from another kitelf |
| **Prebuild** | Out | A kit you have built from components and are holding |
| **Requisition** | Out | A kit delivered to the Kitlocker |

A new movement starts as **Pending** (or **In progress** for a prebuild). When the goods arrive, or
the build is finished, mark it received (**Completed** for a prebuild) and the stock levels move.

### Ordering what you are short of

**Stock Levels** is the starting point.

1. Open **Stock Levels**. Optionally pick a kit in the box at the top and a number of kits, to see
   what building that many would need.
2. Tick **Shortages only** and press **Go**. You now see just the components that are short, with the
   BOM quantity, current stock and what would remain.
3. Use the **Download CSV** icon if you want the list in a spreadsheet.
4. Use **Create Order**. The shortages become the lines of a new order, which you complete with a
   supplier (start typing a contact's name), an ordered date and any quantities you want to change.
   The cross at the end of a line removes it. Press **Create Order**.

### Building a kit (prebuild)

1. From Stock Levels with a kit chosen, press **Create Prebuild** (or use the menu icon).
2. Give it a reference (for example `BUILD-2026-001`), choose the kit and how many, and add an RQ
   number in the note if you are building against a requisition. **Create Prebuild**.
3. The kit's components are listed under the movement. When the kits are built and checked, mark the
   prebuild completed.

### Delivering to the Kitlocker (requisition)

If you have already built the kits as a prebuild, don't re-enter anything: open the prebuild, edit
it, type the RQ number in the **Convert to Requisition** box and press **Convert**. It is one-way:
the kits stay as they are and the RQ number replaces the prebuild's name. It stays recorded against
the person who built it.

A requisition can also be created directly with a reference, date and kit; there is no menu icon for
that yet, so it is reached as `add_requisition.php` in the Stock folder.

### Other movements

**Add Movement** is the general form: choose the type, give a reference and, for an order or
transfer, who it is from. Add lines by component, or add a whole kit and it adds that kit's parts for
you. You can also upload one movement as a CSV file: first line is *supplier, reference, order date,
received date (optional)*, then one line per component: *name, quantity, optional quantity type*.

On a build movement (prebuild or requisition) each kit has its own tab showing just its parts, with a
whole-kit count you can change. Changing the count rescales the parts. Deleting or editing single
lines on those tabs is for stock administrators only.

## Kitelves

**Kit Elves** lists each kitelf with the number of kits and movements they hold, and a link to their
own stock levels. Clicking a name in **List Movements** (the creator column) does the same
filtering, and the page heading tells you whose list you are looking at. Use this when you only want
to see one person's work.

## Kitlocker

The Kitlocker is the shop where finished kits are sold. Stock keeps a record of each Kitlocker item
so its counts can be compared with yours.

**Kitlocker** in the menu shows the Kitlocker groups (General Kits, Postage and so on) as panels
with the number of items in each. Click a group's name to list its items with their Kitlocker ID, price
and a link to the kit or component. Administrators have an edit icon on each group.

Each Kitlocker item carries:

- **KLID** — its Kitlocker ID code, such as `36A`. This is how an item is matched.
- **KL3M** — how many were sold in the last three months.
- **KLSGL** — how many are in stock at the Kitlocker.

### Syncing the Kitlocker stock counts

You don't need to type any of those counts. The **Sync Kitlocker Stock Predict** page reads the
Kitlocker's own "Stock predict" report and updates every matching item in one go.

1. Save the Kitlocker **Stock predict** page as an HTML file from your browser.
2. In the admin menu choose **Sync Kitlocker Stock Predict**.
3. Choose the saved file and upload it.
4. The page reports how many records were updated. Items are matched by KLID; only the stock and
   three-month sales counts change, nothing else on the item.
5. Any code in the report that Stock doesn't know yet is listed under **Unmatched codes**. Each has
   **Add as Assembly** and **Add as Component** buttons: choose which it is (the report doesn't say)
   and the item is created with its stock and sales counts filled in.

Your uploaded file is kept in an archive under its own name, so one upload never overwrites an
earlier one, and the page only does anything when you upload or press one of the Add buttons, so
looking at the page doesn't change anything.

## Permissions

| Permission | What it lets you do |
|---|---|
| View | See kits, components, movements and stock levels |
| Create | Add components, assemblies, movements, prebuilds, orders |
| Update | Edit existing records |
| Delete | Delete stock records |
| Admin | Everything above, plus edit parts-list lines on build movements, edit Kitlocker groups and run the sync |

By default registered users can view, editors can create and update, and administrators do the rest.
