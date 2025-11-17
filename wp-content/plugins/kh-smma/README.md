# KH Social Media Management & Automation (KH-SMMA)

Early scaffold for the Hootsuite-inspired social management layer that will live alongside KH Ad Manager and the broader Marketing Suite.

## Current Capabilities
- Registers dedicated custom post types for:
  - Social accounts (`kh_smma_account`) to store API tokens / membership ties.
  - Social campaigns (`kh_smma_campaign`) for grouping creatives and analytics tags.
  - Scheduled posts (`kh_smma_schedule`) representing queue jobs.
- Defines structured meta schemas for credentials, payloads, delivery modes, and metrics, simplifying migration to custom tables later.
- Adds a hardened credential vault (`wp_{$prefix}kh_smma_tokens`) with AES encryption + repository helpers so provider tokens never reside in plain text meta.
- Provides an admin dashboard (`KH Social` menu) with:
  - Account onboarding form (provider + token storage placeholder).
  - Connected account list with OAuth reconnect buttons for Meta/LinkedIn/X.
  - Quick scheduling form (message, asset picklist from Marketing Suite filters, account, campaign, delivery mode) and validation for timing/delivery.
  - Queue snapshot + rolling 7-day calendar view so marketers can balance workloads.
- Sets up a pluggable cron processor (`kh_smma_process_queue` → `kh_smma_run_queue`) and a queue worker that:
  - Pulls due schedules, marks them as processing, and dispatches via `kh_smma_dispatch_schedule`.
  - Updates status + error meta and fires hooks for analytics.
- Ships channel adapters:
  - `ManualExportAdapter` → stores payload bundles for manual copy/export workflows.
  - `MetaChannelAdapter`, `LinkedInChannelAdapter`, `TwitterChannelAdapter` → stubbed API connectors that now expect encrypted tokens + OAuth onboarding flows before queueing jobs (`OAuthManager` provides start/callback scaffolding).

## Roadmap Hooks
- **Adapters**: Replace stub adapters with live API connectors; continue hooking into `kh_smma_dispatch_schedule`.
- **Analytics**: UTM + KPIs can be injected when schedules are saved, leveraging Marketing Suite helpers.
- **Integrations**: Shared services (KH Ad Manager creatives, library assets, membership metadata) can enqueue jobs via actions/filters (`kh_smma_marketing_assets`, `kh_smma_resolve_asset_content` filters now available).
- **Persistence**: If CPT/meta storage becomes a bottleneck, migrate to dedicated tables using the same schemas defined here.

This README intentionally mirrors the next steps outlined in the project brief so future contributors know where to plug in.
