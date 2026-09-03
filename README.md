# Stock

A [Bitweaver](https://github.com/lsces/bitweaver) package for tracking parts inventory, kit
builds, and movements — components, bills-of-materials, and a full receive/build/deliver
movement ledger, built for [MERG](https://www.merg.org.uk) (the Model Electronic Railway Group)'s
own kit sales and distribution workflow.

**Status: active, in production use.** Built to match one real group's actual workflow and
extended as real needs come up — currently has real MERG-specific pieces (Kitlocker, MERG's own
kit sales portal, is synced directly; "kitelf" — a kit-holder/builder — is baked into the
multi-user filtering) rather than being generic out of the box. The underlying content/movement
model is genuinely generic, though — see "What's planned" below.

## Why this exists

Off-the-shelf inventory tools don't model "a kit is a bill of materials that gets built from
components, then delivered to a member's own kitlocker for onward distribution" well — that's a
specific enough workflow that a generic stock system fights it at every step. This package models
it directly: components and assemblies (kits) are both real content, a bill-of-materials is just
a set of component quantities attached to an assembly, and every stock movement (an order coming
in, a kit being built, a delivery going out) is its own recorded event, not just a running total.

## What it does

- **Components and assemblies (kits)** — both real, editable content, with a genuine
  bill-of-materials on an assembly (a set of quantity xref rows against its component parts, not
  a separate structural-hierarchy table)
- **Movements** — the actual ledger. Four types: `ORDER` (inbound, from a supplier), `TRANS`
  (inbound, from another kit-holder), `PBLD` (a kit built from components, stays with the
  builder), `REQN` (outbound, a kit delivered to a kitlocker) — a prebuild can be converted to a
  requisition in place once delivered, without re-entering it
  - Multi-assembly movements (several kits built/received in one movement) keep each assembly's
    own component lines correctly isolated, even when two assemblies share a component
- **Stock levels** — computed from the movement ledger, not a separately-maintained running
  total; a shortages view (level < 0, on both the plain component list and per-assembly BOM view)
  drives order creation directly
- **Kitlocker sync** — CSV/HTML importers matching kit stock/sales counts against an external
  kitlocker export by ID (a stopgap; the direction is moving new kitlocker items through the
  normal edit UI instead, see `MANUAL.md`)
- **Multi-user filtering** — every list can be scoped to one kit-holder's own components/
  assemblies/movements, with creator names as clickable filter links

## What's planned

**A MERG-agnostic build** — dropping the MERG-specific pages (Kitlocker sync, kitelf
terminology) for a generic core. Liberty's xref group/item structure is what makes this
realistic rather than a rewrite: components/assemblies/movements are already modelled generically,
it's specifically the Kitlocker-sync importers and kitelf-flavoured multi-user filtering that
would need to become optional rather than assumed. Not scoped yet.

See `MANUAL.md` for the full current picture — schema, movement model, and known gotchas
(particularly around Firebird-specific query pitfalls this package has hit more than once).

## Requirements

- [Bitweaver](https://github.com/lsces/bitweaver) 5.x
- [`liberty`](https://github.com/lsces/liberty) package (≥ 5.0.4) — built entirely on Liberty's
  generic content/xref framework, same foundation [`food`](https://github.com/lsces/food) uses
  (Food's own design deliberately mirrors this package throughout); Stock is the heaviest current
  consumer of Liberty's generic content_id+item xref-helper family (see `MANUAL.md`)

See `MANUAL.md` in this repo for the current schema/BOM-storage detail if you're installing it
fresh (`CLAUDE.md` is a dated development log, not a reference — useful for *why* something's
built the way it is, not *how* to set it up).
