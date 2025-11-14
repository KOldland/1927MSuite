# Dual-GPT WordPress Plugin for Research + Authoring

A custom WordPress plugin that integrates OpenAI's Responses API into the Gutenberg editor, providing a dual-GPT authoring experience with research and content generation capabilities.

## Features

- **Dual-Pane Gutenberg Sidebar**: Research and Authoring panes within the WordPress editor
- **OpenAI Integration**: Server-side OpenAI API calls with tool/function calling
- **Secure API Management**: API keys stored server-side only
- **Block JSON Rendering**: AI outputs converted to validated Gutenberg blocks
- **Audit Trail**: Complete logging of prompts, responses, token usage, and costs
- **Tool Ecosystem**: Research tools (web search, URL fetching) and Author tools (outlining, style validation)

## Installation

1. Download or clone this plugin into your `wp-content/plugins/` directory
2. Activate the plugin through the WordPress admin dashboard
3. Configure your OpenAI API key (see Configuration section)

## Configuration

### API Key Setup

Set your OpenAI API key using one of these methods (in order of priority):

1. **wp-config.php constant**:
   ```php
   define('DUAL_GPT_OPENAI_API_KEY', 'your-api-key-here');
   ```

2. **WordPress option** (via admin interface - coming in future update)

3. **Environment variable**:
   ```bash
   export OPENAI_API_KEY=your-api-key-here
   ```

### Database Tables

The plugin automatically creates the following custom tables on activation:

- `wp_ai_sessions`: Stores research/author sessions
- `wp_ai_jobs`: Tracks AI API calls and responses
- `wp_ai_presets`: Stores GPT presets and configurations
- `wp_ai_audit`: Audit log for all AI interactions
- `wp_ai_budgets`: Token usage limits and tracking

## Usage

### Gutenberg Editor Integration

1. Open any post/page in the Gutenberg editor
2. Look for the "Dual-GPT Authoring" panel in the sidebar (may need to enable it)
3. Use the Research pane to gather information
4. Use the Author pane to generate content
5. Insert generated blocks directly into your post

### REST API Endpoints

The plugin provides the following REST API endpoints:

- `POST /wp-json/dual-gpt/v1/sessions` - Create new session
- `POST /wp-json/dual-gpt/v1/jobs` - Start AI job
- `GET /wp-json/dual-gpt/v1/jobs/{id}/stream` - Stream job results
- `GET /wp-json/dual-gpt/v1/presets` - Get available presets
- `GET /wp-json/dual-gpt/v1/audit` - View audit logs
- `GET /wp-json/dual-gpt/v1/budgets` - Check token budgets

## Architecture

### Components

- **Frontend**: React-based Gutenberg sidebar
- **Backend**: WordPress REST API endpoints
- **Job Runner**: Handles OpenAI API calls and streaming
- **Tool Layer**: PHP classes for research and author tools
- **Storage Layer**: Custom database tables for persistence
- **Block Renderer**: Converts AI output to Gutenberg blocks

### Data Flow

1. User enters prompt in Research/Author pane
2. Frontend sends request to REST API
3. Backend creates job and session records
4. Job runner calls OpenAI API with appropriate tools
5. AI response is processed and validated
6. Results streamed back to frontend
7. User can insert generated blocks into editor

## Development

### File Structure

```
dual-gpt-wordpress-plugin/
├── dual-gpt-wordpress-plugin.php     # Main plugin file
├── includes/
│   ├── class-dual-gpt-plugin.php     # Main plugin class
│   ├── class-db-handler.php          # Database operations
│   ├── class-openai-connector.php    # OpenAI API integration
│   └── tools/
│       ├── class-research-tools.php  # Research tool implementations
│       └── class-author-tools.php    # Author tool implementations
├── assets/
│   ├── js/
│   │   └── sidebar.js                # Gutenberg sidebar React component
│   └── css/
│       └── sidebar.css               # Sidebar styles
└── README.md                         # This file
```

### Adding New Tools

1. Add tool method to appropriate tool class (Research or Author)
2. Add tool definition to `get_tool_definitions()` method
3. Update OpenAI connector if needed
4. Test tool execution

### Block Schema

AI outputs follow a canonical Blocks JSON schema (v1):

```json
{
  "version": 1,
  "blocks": [
    {
      "type": "heading",
      "level": 2,
      "content": "Heading text"
    },
    {
      "type": "paragraph",
      "content": "Paragraph content"
    }
  ],
  "metadata": {
    "title": "Content title",
    "excerpt": "Brief summary",
    "tags": ["tag1", "tag2"],
    "references": [
      {
        "n": 1,
        "apa": "APA citation",
        "url": "https://source.url"
      }
    ]
  },
  "compliance": {
    "subheads": 2,
    "long_short_ratio": "balanced",
    "emdash_count": 0,
    "reversal_marker_present": true,
    "micro_transitions": 2
  }
}
```

## Security

- API keys never exposed to client-side JavaScript
- All AI calls made server-side
- REST endpoints protected with WordPress nonces
- User capability checks for different permission levels
- Token usage limits and budget enforcement

## Current Implementation Status

### ✅ Sprint 1 - Core Infrastructure (COMPLETED)
- Database schema and migrations (5 custom tables)
- REST API endpoints for sessions, jobs, streaming, presets, audit, and budgets
- Basic Gutenberg sidebar UI (two panes)
- OpenAI connector with tool support
- Job runner for executing OpenAI calls with tool loops
- Server-Sent Events streaming for real-time responses
- Blocks JSON v1 schema validation and Gutenberg block insertion

### 🔄 Sprint 2 - Research + Author Flow (IN PROGRESS)
- Research tools implementation (web_search, fetch_url, summarize_pdf, citation_check)
- Author tools implementation (outline_from_brief, expand_section, style_guard, citation_guard)
- Preset management for different GPT configurations
- Token usage limits and budget enforcement

### 📋 Sprint 3 - Validation + Governance (PLANNED)
- Admin UI for API key management, budget settings, and audit viewing
- Enhanced error handling and recovery
- Comprehensive audit logging
- Advanced validation and compliance features

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

GPL v2 or later

## Support

For support or feature requests, please create an issue in the repository.