# KHM SEO Plugin

A comprehensive SEO plugin for WordPress, built specifically for content marketing and publishing platforms. Provides advanced SEO features including meta tag management, XML sitemaps, schema markup, and real-time content analysis.

## Features

### Core SEO Features
- **Meta Tag Management**: Custom titles, descriptions, keywords for posts, pages, and terms
- **Open Graph & Twitter Cards**: Social media optimization
- **XML Sitemaps**: Automatic sitemap generation for posts, pages, categories, and tags
- **Schema Markup**: Rich snippets for articles, organizations, and more
- **Robots Meta**: Fine-grained control over search engine indexing
- **Canonical URLs**: Prevent duplicate content issues

### Content Analysis
- **Real-time SEO Analysis**: Live content scoring as you write
- **Focus Keyword Optimization**: Keyword density and placement analysis
- **Readability Checks**: Content length, heading structure, and image optimization
- **SEO Recommendations**: Actionable suggestions to improve content

### Technical SEO Tools
- **Robots.txt Editor**: Customize your robots.txt file
- **Search Engine Verification**: Google, Bing, and Pinterest verification tags
- **Site Health Monitoring**: System status and SEO health checks

### Admin Interface
- **Intuitive Dashboard**: Easy-to-use admin interface
- **Post Meta Boxes**: SEO controls directly in post/page edit screens
- **Bulk Editing**: Manage SEO settings across multiple content pieces
- **Social Media Previews**: See how your content will appear on social platforms

## Installation

1. Upload the `khm-seo` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin through the 'KHM SEO' menu in WordPress admin

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## File Structure

```
khm-seo/
├── khm-seo.php                 # Main plugin file
├── uninstall.php              # Uninstall cleanup
├── src/                       # Plugin source code
│   ├── Core/                  # Core functionality
│   │   ├── Autoloader.php     # PSR-4 autoloader
│   │   ├── Plugin.php         # Main plugin class
│   │   ├── Activator.php      # Activation handler
│   │   └── Deactivator.php    # Deactivation handler
│   ├── Meta/                  # Meta tag management
│   │   └── MetaManager.php    # Meta tags and social media
│   ├── Schema/                # Schema markup
│   │   └── SchemaManager.php  # Structured data generation
│   ├── Sitemap/               # XML sitemaps
│   │   └── SitemapManager.php # Sitemap generation
│   ├── Admin/                 # Admin interface
│   │   └── AdminManager.php   # Dashboard and settings
│   ├── Tools/                 # SEO tools
│   │   └── ToolsManager.php   # Analysis and utilities
│   └── Utils/                 # Utilities
│       └── DatabaseManager.php # Database operations
├── assets/                    # Frontend assets
│   ├── css/
│   │   └── admin.css          # Admin styles
│   └── js/
│       └── admin.js           # Admin JavaScript
├── templates/                 # Admin templates
│   └── admin/
│       └── general.php        # General settings template
└── languages/                 # Translation files
```

## Database Schema

The plugin creates two custom tables:

### khm_seo_posts
Stores SEO data for posts and pages:
- Custom titles and descriptions
- Social media meta tags
- Schema markup settings
- Focus keywords and SEO scores

### khm_seo_terms
Stores SEO data for categories, tags, and custom taxonomies:
- Custom titles and descriptions
- Social media meta tags
- Robots meta settings

## Hooks and Filters

### Actions
- `khm_seo_footer_output` - Add custom footer content
- `khm_seo_generate_sitemap` - Triggered during sitemap generation
- `khm_seo_sitemap_generated` - After sitemap generation completes

### Filters
- `khm_seo_title` - Filter the SEO title
- `khm_seo_description` - Filter the meta description
- `khm_seo_schema_data` - Modify schema markup data

## Configuration

### General Settings
- Site information and branding
- Title separators and formats
- Knowledge Graph settings
- Social media profiles

### Titles & Meta
- Title format templates
- Meta tag settings
- Social media configuration

### XML Sitemaps
- Enable/disable sitemaps
- Content type inclusion
- Sitemap limits and settings

### Schema Markup
- Enable structured data
- Article types and settings
- Organization information

### Tools
- Robots.txt customization
- Search engine verification
- System status monitoring

## Performance

The plugin is designed for optimal performance:
- Minimal database queries
- Efficient caching mechanisms
- Asynchronous content analysis
- Lightweight frontend footprint

## Development

### Extending the Plugin

The plugin follows WordPress coding standards and provides hooks for customization:

```php
// Add custom schema data
add_filter( 'khm_seo_schema_data', function( $schema ) {
    // Modify schema markup
    return $schema;
});

// Custom SEO analysis
add_filter( 'khm_seo_content_analysis', function( $analysis, $content ) {
    // Add custom analysis checks
    return $analysis;
}, 10, 2 );
```

### Code Standards

- PSR-4 autoloading
- WordPress coding standards
- Object-oriented architecture
- Comprehensive documentation

## Support

For support and feature requests, please contact the KHM Development Team.

## License

GPL v2 or later

## Changelog

### 1.0.0
- Initial release
- Core SEO functionality
- Meta tag management
- XML sitemaps
- Schema markup
- Admin interface
- Content analysis tools