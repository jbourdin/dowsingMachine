# Legacy: the deck lending tool

> **Audience:** Developer · **Scope:** History

← Back to [Main Documentation](docs.md) | [README](../README.md)

---

The project started as **Expanded Decks**, served on expandeddecks.app: a tool for a community of players to share a library of physical Pokemon TCG decks in the Expanded format. Players registered their decks, declared upcoming events, asked to borrow decks and followed each loan from request to return.

Usage showed that people came for the deck lists and the archetype content, not for lending. The lending workflow was retired in 2026 ([#790](https://github.com/jbourdin/dowsingMachine/issues/790)) and expandeddecks.app was closed on 2026-10-26. The site now runs on dowsingmachine.com only. The lending code is still in the repository until #790 decides what to delete.

This page keeps the original description of the tool.

## Original pitch

The Expanded format offers a lot of deck diversity and deep strategy, but its large card pool can be a barrier for newcomers: out-of-print cards, unfamiliar combos and a significant upfront cost. Expanded Decks existed to lower that barrier by making it easy for experienced players to **lend their decks** to others at organized events. A newcomer could try a real competitive deck, enjoy the format, and decide whether to build their own.

The full reasoning is in [philosophy.md](philosophy.md).

Physical deck boxes were identified by **Zebra-printed labels** with a scannable code, for fast check-in and check-out at events.

## Retired features

| Domain | Summary |
|--------|---------|
| **F1 — User Management** | Public registration with email verification, screen name, player ID. Global roles: player, organizer, admin. Per-event staff. MFA and Pokemon SSO were planned. |
| **F2 — Deck Library** | Register physical decks, browse the catalog, track availability. |
| **F3 — Event Management** | Declare events, list upcoming and past events, register participation, assign event staff. |
| **F4 — Borrow Workflow** | Request, approve, hand-off, return: the full lending lifecycle with staff-delegated lending, custody tracking, history and overdue tracking. |
| **F5 — Zebra Label Printing** | Generate ZPL labels, push them to a Zebra printer via PrintNode, scan barcodes to identify a deck. |
| **F7 — Administration** | Dashboard with borrow reminders. |
| **F8 — Notifications** | Borrow notifications. |
| **F13 — Bookmarks** | Bookmark decks, events and archetypes (planned). |
| **F15 — Freeplay Matchmaking** | Player readiness and match proposals at events (planned). |

The details stay in [features.md](features.md) under each feature ID.

## Related documents

- [Borrow Model](models/borrow.md), [Deck Model](models/deck.md), [Event Model](models/event.md), [Notification Model](models/notification.md)
- [Overdue tracking plan](plans/overdue_tracking.md)
- [Scanner](technicalities/scanner.md), [Camera scanner](technicalities/camera_scanner.md), [PDF label](technicalities/pdf_label.md)
- [Zebra ZPL Programming Guide](https://www.zebra.com/us/en/support-downloads/knowledge-articles/ait/zpl-command-information-and-cross-reference.html)
