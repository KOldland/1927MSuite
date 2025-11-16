# KH Events Multi-Timezone Support Documentation

## Overview
The KH Events plugin provides comprehensive multi-timezone support, allowing events to be created in any timezone and automatically displayed to users in their preferred timezone. This enables global event management with proper timezone handling.

## Features

### 1. Event Timezone Management
- **Per-Event Timezones**: Each event can have its own timezone
- **Timezone Validation**: Ensures only valid PHP timezone identifiers are used
- **Admin Interface**: Easy timezone selection in event editor
- **Live Preview**: Shows current time in selected timezone

### 2. User Timezone Preferences
- **User Profiles**: Users can set their preferred timezone
- **Automatic Detection**: Browser-based timezone detection
- **Persistent Settings**: Timezone preferences are saved per user
- **Admin Override**: Administrators can manage user timezones

### 3. Automatic Timezone Conversion
- **Real-time Conversion**: Times automatically convert to user's timezone
- **Frontend Display**: Event times show in user's preferred timezone
- **Timezone Labels**: Optional timezone abbreviations display
- **DST Handling**: Automatic daylight saving time adjustments

### 4. Admin Tools
- **Bulk Updates**: Change timezones for multiple events
- **Timezone Converter**: Tool for manual timezone conversions
- **Settings Management**: Global timezone configuration
- **Validation Tools**: Ensure timezone data integrity

## Configuration

### Global Settings
Navigate to **KH Events > Settings** to configure:

- **Default Event Timezone**: Default timezone for new events
- **Enable User Timezones**: Allow users to set preferences
- **Show Timezone Labels**: Display timezone info on frontend

### User Preferences
Users can set their timezone preference in:
- User profile settings
- Automatic browser detection
- Manual selection from timezone list

## Usage

### Creating Events with Timezones

1. **Edit/Create Event**: Go to Events > Add New
2. **Set Timezone**: In the "Event Timezone" meta box, select the event's timezone
3. **Live Preview**: See current time in the selected timezone
4. **Save Event**: Timezone is saved with the event

### User Timezone Setup

1. **Profile Settings**: Users can visit their profile to set timezone
2. **Automatic Detection**: Plugin can suggest timezone based on browser
3. **Manual Selection**: Choose from comprehensive timezone list

### Frontend Display

- **Automatic Conversion**: Event times automatically show in user's timezone
- **Timezone Indicators**: Optional timezone labels (e.g., "EST", "PST")
- **Consistent Formatting**: Maintains WordPress date/time format settings

## Technical Implementation

### Timezone Classes
```php
KH_Event_Timezone::instance()->get_user_timezone($user_id);
KH_Event_Timezone::instance()->set_event_timezone($event_id, $timezone);
KH_Event_Timezone::instance()->convert_datetime($datetime, $from_tz, $to_tz);
```

### Available Methods

#### Core Functionality
- `get_user_timezone($user_id)` - Get user's preferred timezone
- `set_user_timezone($timezone, $user_id)` - Set user's timezone preference
- `get_event_timezone($event_id)` - Get event's timezone
- `set_event_timezone($event_id, $timezone)` - Set event's timezone

#### Timezone Utilities
- `is_valid_timezone($timezone)` - Validate timezone identifier
- `convert_datetime($datetime, $from_tz, $to_tz)` - Convert between timezones
- `format_datetime_for_user($datetime, $event_tz, $user_id)` - Format for user display
- `get_timezone_offset($timezone)` - Get UTC offset in hours
- `get_timezone_abbr($timezone)` - Get timezone abbreviation

#### Admin Tools
- `get_available_timezones()` - Get all supported timezones
- `get_timezone_select_html($selected, $name, $id)` - Generate HTML select
- `add_timezone_meta_box()` - Add timezone meta box to events
- `register_timezone_settings()` - Register admin settings

### JavaScript API

#### Frontend Features
```javascript
// Automatic timezone conversion
$('.kh-event-time').each(function() {
    // Plugin automatically converts times
});

// User timezone selector
$('#kh-user-timezone-selector').on('change', function() {
    var timezone = $(this).val();
    // Saves user preference via AJAX
});
```

#### Admin Tools
```javascript
// Timezone preview
updateTimezonePreview();

// Bulk timezone updates
$('#apply-bulk-timezone').on('click', function() {
    // Updates multiple events
});
```

## AJAX Endpoints

### User Timezone Management
```
POST /wp-admin/admin-ajax.php
action: kh_save_user_timezone
timezone: America/New_York
```

### Event Timezone Updates
```
POST /wp-admin/admin-ajax.php
action: kh_update_event_timezone
event_id: 123
timezone: Europe/London
```

### Timezone Information
```
POST /wp-admin/admin-ajax.php
action: kh_get_timezone_info
timezone: Asia/Tokyo
```

### Timezone Conversion
```
POST /wp-admin/admin-ajax.php
action: kh_convert_timezone
datetime: 2024-01-15 10:00:00
from_timezone: America/New_York
to_timezone: Europe/London
```

## REST API Integration

Timezone information is included in REST API responses:

```json
{
  "id": 123,
  "title": "Global Conference",
  "start_date": "2024-03-15",
  "start_time": "14:00",
  "timezone": "America/New_York",
  "timezone_offset": -5,
  "timezone_abbr": "EST"
}
```

## Supported Timezones

The plugin supports all PHP timezone identifiers, with common timezones prioritized:

### Major Timezones
- **Eastern Time**: America/New_York
- **Central Time**: America/Chicago
- **Mountain Time**: America/Denver
- **Pacific Time**: America/Los_Angeles
- **GMT/BST**: Europe/London
- **CET/CEST**: Europe/Paris, Europe/Berlin
- **JST**: Asia/Tokyo
- **CST**: Asia/Shanghai

### Complete List
All 400+ PHP supported timezones are available for selection.

## Database Storage

### Event Meta
- `_kh_event_timezone`: Event's timezone identifier

### User Meta
- `kh_events_timezone`: User's preferred timezone

### Options
- `kh_events_default_timezone`: Site default timezone
- `kh_events_enable_user_timezones`: Enable user preferences
- `kh_events_show_timezone_labels`: Show timezone labels

## Frontend Integration

### Shortcode Support
```php
// Automatic timezone conversion in shortcodes
echo do_shortcode('[kh_event_time event_id="123"]');
```

### Template Functions
```php
// Get formatted time for user
$formatted_time = KH_Event_Timezone::instance()->format_datetime_for_user(
    $datetime, $event_timezone, $user_id
);
```

### CSS Classes
```css
.kh-event-time { /* Event time display */ }
.timezone-indicator { /* Timezone abbreviation */ }
.kh-user-timezone-selector { /* User timezone selector */ }
.kh-timezone-message { /* Success/error messages */ }
```

## Performance Considerations

### Caching
- Timezone conversions are cached where possible
- User timezone preferences are cached
- AJAX responses include cache headers

### Optimization
- Minimal database queries for timezone lookups
- Efficient timezone validation
- Lazy loading of timezone data

## Troubleshooting

### Common Issues

**Times not converting**: Check user timezone preference is set
**Invalid timezone**: Ensure timezone identifier is valid PHP timezone
**DST issues**: Plugin handles DST automatically
**Performance**: Check for excessive AJAX calls

### Debug Tools

Enable debugging in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('KH_EVENTS_TIMEZONE_DEBUG', true);
```

### Validation

Run the timezone test:
```bash
php test_timezone.php
```

## Integration Examples

### Theme Integration
```php
// In single-event.php
$event_timezone = KH_Event_Timezone::instance()->get_event_timezone(get_the_ID());
$user_timezone = KH_Event_Timezone::instance()->get_user_timezone();

$start_datetime = get_post_meta(get_the_ID(), '_kh_event_start_date', true) . ' ' .
                 get_post_meta(get_the_ID(), '_kh_event_start_time', true);

$display_time = KH_Event_Timezone::instance()->format_datetime_for_user(
    $start_datetime, $event_timezone, get_current_user_id()
);

echo '<p>Event starts: ' . $display_time . '</p>';
```

### Plugin Integration
```php
// Hook into timezone conversion
add_filter('kh_event_display_time', function($time, $event_id, $user_id) {
    // Custom time formatting
    return $time . ' (Custom)';
}, 10, 3);
```

### JavaScript Integration
```javascript
// Custom timezone handling
jQuery(document).on('kh_timezone_converted', function(event, data) {
    console.log('Time converted:', data);
});
```

## Security

### Input Validation
- All timezone inputs validated against PHP timezone list
- User permissions checked for timezone updates
- AJAX requests include nonce verification

### Data Sanitization
- Timezone identifiers sanitized
- User inputs escaped for output
- Database queries use prepared statements

## Future Enhancements

### Planned Features
- **Calendar Integration**: Sync with Google Calendar, Outlook
- **Recurring Events**: Timezone-aware recurrence patterns
- **Mobile App**: Timezone support in mobile applications
- **Webhook Notifications**: Timezone-aware event notifications

### API Expansions
- **Bulk Timezone Operations**: Update multiple events
- **Timezone Groups**: Organize timezones by region
- **Custom Timezone Rules**: Support for non-standard timezones

## Support

For technical support or feature requests:
- Check the implementation documentation
- Review the test files for examples
- Enable debugging for troubleshooting
- Contact the development team

## Version History

- **v1.0.0**: Initial multi-timezone implementation
- Complete timezone management system
- User preference system
- Automatic conversion engine
- Admin tools and settings