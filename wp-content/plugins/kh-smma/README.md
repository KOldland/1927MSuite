# KH Social Media Management & Automation (KH-SMMA)

Early scaffold for the Hootsuite-inspired social management layer that will live alongside KH Ad Manager and the broader Marketing Suite.

## Current Capabilities
- Registers dedicated custom post types for:
  - Social accounts (`kh_smma_account`) to store API tokens / membership ties.
  - Social campaigns (`kh_smma_campaign`) for grouping creatives and analytics tags.
  - Scheduled posts (`kh_smma_schedule`) representing queue jobs.
- Defines structured meta schemas for credentials, payloads, delivery modes, and metrics, simplifying migration to custom tables later.
- Adds a hardened credential vault (`wp_{$prefix}kh_smma_tokens`) with AES encryption + repository helpers so provider tokens never reside in plain text meta.
- Implements OAuth authorization flows for Meta, LinkedIn, and X/Twitter (client IDs/secrets provided via constants or the `kh_smma_provider_config` filter). State handling + PKCE ensure secure exchanges and metadata (page IDs, LinkedIn URNs, Twitter user handles) are stored with the encrypted tokens.
- Provides an admin dashboard (`KH Social` menu) with:
  - Account onboarding form (provider + token storage placeholder).
  - Connected account list with OAuth reconnect buttons for Meta/LinkedIn/X.
  - Quick scheduling form (message, asset picklist from Marketing Suite filters, account, campaign, delivery mode) and validation for timing/delivery.
  - Queue snapshot + rolling 7-day calendar view so marketers can balance workloads.
- Introduces granular capabilities (`kh_smma_view_queue`, `kh_smma_schedule_posts`, `kh_smma_manage_accounts`) so editors/marketers can work without full admin rights, plus dedicated audit log / permissions submenu pages (CSV export supported).
- Audit log UI now includes action/date/detail filters, pagination, and CSV export for easier compliance reviews.
- Sets up a pluggable cron processor (`kh_smma_process_queue` → `kh_smma_run_queue`) and a queue worker that:
  - Pulls due schedules, marks them as processing, and dispatches via `kh_smma_dispatch_schedule`.
  - Updates status + error meta, captures telemetry for every handler (sandbox, approval, live), and fires hooks used by analytics/third parties.
- Ships channel adapters:
  - `ManualExportAdapter` → stores payload bundles for manual copy/export workflows.
  - `MetaChannelAdapter`, `LinkedInChannelAdapter`, `TwitterChannelAdapter` → call the real Graph/LinkedIn/Twitter endpoints using the encrypted tokens issued during OAuth onboarding (expects provider-specific metadata like page IDs or author URNs). Payload builders now support Marketing Suite assets (links/media) so creatives stay consistent across networks.
  - Adapters emit structured telemetry, rate-limit headers, and engagement metrics (reach/clicks/etc.) that feed the dashboard’s analytics snapshot.

## Sandbox, Approval, and Telemetry Workflow

- **Sandbox mode** – each account surface has a “Sandbox Mode” switch. When enabled, schedules never hit the external API; their payloads drop into `_kh_smma_last_telemetry` and the queue status flips to `sandboxed`. This allows copy/platform QA without creating duplicate social posts.
- **Approval requirements** – accounts can also require explicit approvals. When toggled on, newly created schedules start in `pending_approval` and appear in the queue with approve/deny buttons. Approving resets the status to `pending` (ready for dispatch) while denying records `_kh_smma_approval_status = denied` + `_kh_smma_last_error`.
- **Telemetry stream** – every state change (sandbox preview, approval decision, dispatch success/failure) passes through `kh_smma_schedule_status_changed`. The telemetry payload includes provider, mode (sandbox/manual/live), any response body/HTTP codes, and optional metrics (clicks, reach, etc.). The admin dashboard’s “Preview Dispatch Telemetry” table is a realtime view of that structured meta.
- **Analytics snapshot** – `AnalyticsFeedbackService` aggregates telemetry into:
  - Overall status counters (completed/failed/sandboxed/etc.).
  - Provider-level summaries (Meta vs LinkedIn vs Twitter vs manual export).
  - Campaign-level summaries when `_kh_smma_campaign_id` is set.
  - The 10 most recent events with their payload/metric summaries for quick forensic QA.

## Lifecycle Simulator & QA Controls

- A **“Run Lifecycle Demo”** button (visible to `kh_smma_manage_accounts`) spins up a synthetic schedule using the lifecycle simulator service. It walks the queue through sandbox preview, approval, and live completion so telemetry/analytics aren’t empty on a fresh install.
- The simulator also seeds `_kh_smma_result_metrics` with demo reach/click counts, letting design/QA teams validate the analytics cards before real tokens are provided.
- Because the simulator only relies on local CPT/meta, it is safe to run repeatedly in dev/staging without pinging provider APIs.

## CLI & Simulator Workflows

- Run `wp kh-smma lifecycle-sim` to execute the simulator from WP-CLI. The command reuses the same Elementor bypass filter that powers `/tmp/run_lifecycle_demo.php`, creates a demo account if needed, and prints a JSON payload (schedule ID, status, telemetry, metrics, analytics snapshot) for CI/QA to archive.
- If WP-CLI is unavailable on a given box, `/tmp/run_lifecycle_demo.php` serves as a direct PHP entry point; it loads WordPress, strips Elementor via the same filter, and boots the plugin before calling `LifecycleSimulator`.
- These tools intentionally avoid touching provider APIs. Once we have real Meta/LinkedIn/Twitter OAuth tokens, QA can inject them via the `kh_smma_provider_config` filter or constants (see **Provider Token Setup**) and rerun the command to validate true dispatch + telemetry flows without changing automation scripts.
- The MU plugin (`wp-content/mu-plugins/kh-cli-elementor-bypass.php`) governs Elementor loading during CLI execution. Set `KH_SMMA_FORCE_ELEMENTOR` if you need Elementor present (for example, while running Gutenberg or Elementor-specific tests), or set `KH_SMMA_SKIP_ELEMENTOR` to hard-disable it for bespoke scripts.

## Provider Token Setup

- Tokens are stored in the encrypted `kh_smma_tokens` vault. When connecting an account manually, the onboarding form saves whatever credential blob you paste; for OAuth-capable providers (Meta/LinkedIn/Twitter), you can also supply credentials by hooking `kh_smma_provider_config` or defining constants such as:

```php
define( 'KH_SMMA_META_APP_ID', '...' );
define( 'KH_SMMA_META_APP_SECRET', '...' );
```

- Adapters read page IDs, LinkedIn URNs, and Twitter handles from the saved token metadata. For CLI scripts or migrations you can call `TokenRepository::save_token()` directly with the same structure the OAuth manager produces.
- Engagement metrics (post impressions, clicks, likes, etc.) are fetched via `EngagementMetricsService` after each dispatch. Ensure your stored tokens include the required scopes (e.g., `pages_read_engagement` for Meta) so the fetcher can hit the insights endpoints.

## CLI & Automation Notes

- Some CLI workflows (e.g., running `/tmp/run_lifecycle_demo.php` or future WP-CLI commands) don’t need Elementor. The repo now ships with a small MU plugin (`wp-content/mu-plugins/kh-cli-elementor-bypass.php`) that removes Elementor from `option_active_plugins` whenever PHP is running via CLI. You can override the behavior with the following constants:
  - `define( 'KH_SMMA_SKIP_ELEMENTOR', true );` – force-disable Elementor for the current request (handy for custom scripts).
  - `define( 'KH_SMMA_FORCE_ELEMENTOR', true );` – ensure Elementor always loads, even during CLI execution.
- This defensive filter prevents `Elementor\Core\Wp_Api` fatals when WordPress boots outside of wp-admin, allowing the lifecycle simulator and future automation hooks to run in local/dev environments.

## Roadmap Hooks
- **Adapters**: Replace stub adapters with live API connectors; continue hooking into `kh_smma_dispatch_schedule`.
- **Analytics**: UTM + KPIs can be injected when schedules are saved, leveraging Marketing Suite helpers. Future work can enrich the analytics snapshot with downstream conversions fetched from Marketing Suite services via `PluginRegistry::call_service()`.
- **Integrations**: Shared services (KH Ad Manager creatives, library assets, membership metadata) can enqueue jobs via actions/filters (`kh_smma_marketing_assets`, `kh_smma_resolve_asset_content` filters now available).
- **Governance**: Continue enhancing the audit log filtering/export tooling (e.g., per-user filters, saved searches) and surface capability changes in the permissions UI so site owners can delegate safely.
- **Persistence**: If CPT/meta storage becomes a bottleneck, migrate to dedicated tables using the same schemas defined here.

This README intentionally mirrors the next steps outlined in the project brief so future contributors know where to plug in.
