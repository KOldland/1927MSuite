# KH Events REST API Documentation

## Overview
The KH Events plugin provides comprehensive REST API endpoints for managing events, locations, bookings, and related data. The API follows WordPress REST API standards and provides full CRUD operations.

## Base URL
```
https://yoursite.com/wp-json/kh-events/v1/
```

## Authentication
- **Public Endpoints**: Events, locations, categories, tags, search, calendar feeds
- **Authenticated Endpoints**: Creating/updating/deleting requires appropriate WordPress capabilities
- **Booking Creation**: Public access allowed for event bookings

## Events Endpoints

### GET /events
Retrieve a collection of events.

**Parameters:**
- `page` (integer): Current page (default: 1)
- `per_page` (integer): Items per page (default: 10)
- `search` (string): Search term
- `after` (string): Events after this date (ISO8601)
- `before` (string): Events before this date (ISO8601)
- `status` (array): Event statuses (publish, future, draft, pending, private, trash)
- `categories` (array): Category IDs
- `tags` (array): Tag IDs
- `location` (integer): Location ID
- `start_date` (string): Events starting after this date
- `end_date` (string): Events ending before this date

**Example:**
```bash
GET /wp-json/kh-events/v1/events?per_page=5&status=publish
```

### GET /events/{id}
Retrieve a single event.

**Parameters:**
- `id` (integer): Event ID (required)
- `context` (string): Response context (default: view)

### POST /events
Create a new event.

**Required Parameters:**
- `title` (string): Event title
- `start_date` (string): Start date (YYYY-MM-DD)

**Optional Parameters:**
- `content` (string): Event description
- `excerpt` (string): Short description
- `status` (string): Post status (publish, draft, etc.)
- `start_time` (string): Start time (HH:MM)
- `end_date` (string): End date (YYYY-MM-DD)
- `end_time` (string): End time (HH:MM)
- `location_id` (integer): Location ID
- `categories` (array): Category IDs
- `tags` (array): Tag IDs
- `featured_image` (integer): Media ID
- `custom_fields` (object): Custom field data

**Example:**
```bash
POST /wp-json/kh-events/v1/events
Content-Type: application/json

{
  "title": "Summer Music Festival",
  "content": "A great outdoor music event",
  "start_date": "2024-07-15",
  "start_time": "19:00",
  "end_date": "2024-07-15",
  "end_time": "23:00",
  "location_id": 123,
  "categories": [1, 2],
  "status": "publish"
}
```

### PUT /events/{id}
Update an existing event.

**Parameters:** Same as POST, plus:
- `id` (integer): Event ID (URL parameter)

### DELETE /events/{id}
Delete an event.

**Parameters:**
- `id` (integer): Event ID (URL parameter)
- `force` (boolean): Force delete (default: false)

## Locations Endpoints

### GET /locations
Retrieve locations.

**Parameters:**
- `page` (integer): Current page
- `per_page` (integer): Items per page

### GET /locations/{id}
Retrieve a single location.

### POST /locations
Create a new location.

**Required Parameters:**
- `title` (string): Location name

**Optional Parameters:**
- `content` (string): Location description
- `address` (string): Street address
- `city` (string): City
- `state` (string): State/Province
- `zip` (string): Postal code
- `country` (string): Country
- `latitude` (number): Latitude coordinate
- `longitude` (number): Longitude coordinate

### PUT /locations/{id}
Update a location.

### DELETE /locations/{id}
Delete a location.

## Bookings Endpoints

### GET /bookings
Retrieve bookings (admin access required).

### GET /bookings/{id}
Retrieve a single booking.

### POST /bookings
Create a new booking.

**Required Parameters:**
- `event_id` (integer): Event ID
- `attendee_name` (string): Attendee name
- `attendee_email` (string): Attendee email

**Optional Parameters:**
- `user_id` (integer): WordPress user ID
- `attendee_phone` (string): Phone number
- `quantity` (integer): Number of tickets (default: 1)
- `notes` (string): Additional notes

**Example:**
```bash
POST /wp-json/kh-events/v1/bookings
Content-Type: application/json

{
  "event_id": 456,
  "attendee_name": "John Doe",
  "attendee_email": "john@example.com",
  "quantity": 2,
  "notes": "VIP seating preferred"
}
```

### PUT /bookings/{id}
Update a booking.

### DELETE /bookings/{id}
Delete a booking.

## Taxonomy Endpoints

### GET /categories
Retrieve event categories.

### GET /tags
Retrieve event tags.

## Search Endpoint

### GET /search
Search events.

**Parameters:**
- `q` (string): Search query (required)
- `per_page` (integer): Results per page (default: 10)

**Example:**
```bash
GET /wp-json/kh-events/v1/search?q=music&per_page=5
```

## Calendar Feed Endpoint

### GET /calendar
Get calendar feed data.

**Parameters:**
- `format` (string): Output format - 'json' or 'ical' (default: json)
- `start_date` (string): Start date filter
- `end_date` (string): End date filter

**Examples:**
```bash
# JSON feed
GET /wp-json/kh-events/v1/calendar?format=json

# iCal feed
GET /wp-json/kh-events/v1/calendar?format=ical
```

## Response Formats

### Event Response
```json
{
  "id": 123,
  "title": {
    "rendered": "Summer Music Festival"
  },
  "content": {
    "rendered": "<p>A great outdoor music event</p>"
  },
  "excerpt": {
    "rendered": "A great outdoor music event"
  },
  "status": "publish",
  "start_date": "2024-07-15",
  "start_time": "19:00",
  "end_date": "2024-07-15",
  "end_time": "23:00",
  "location": {
    "id": 456,
    "name": "Central Park",
    "address": "123 Main St"
  },
  "categories": [
    {
      "id": 1,
      "name": "Music"
    }
  ],
  "tags": [],
  "featured_image": "https://example.com/wp-content/uploads/image.jpg",
  "permalink": "https://example.com/events/summer-music-festival/",
  "custom_fields": {
    "ticket_price": "25.00",
    "max_attendees": "500"
  }
}
```

### Location Response
```json
{
  "id": 456,
  "title": {
    "rendered": "Central Park"
  },
  "content": {
    "rendered": "<p>A beautiful urban park</p>"
  },
  "address": "123 Main Street",
  "city": "New York",
  "state": "NY",
  "zip": "10001",
  "country": "USA",
  "latitude": "40.7829",
  "longitude": "-73.9654",
  "permalink": "https://example.com/locations/central-park/"
}
```

### Booking Response
```json
{
  "id": 789,
  "event": {
    "id": 123,
    "title": "Summer Music Festival",
    "permalink": "https://example.com/events/summer-music-festival/"
  },
  "user_id": null,
  "attendee_name": "John Doe",
  "attendee_email": "john@example.com",
  "attendee_phone": "555-0123",
  "quantity": 2,
  "notes": "VIP seating preferred",
  "booking_date": "2024-06-01 10:30:00",
  "date": "2024-06-01T10:30:00"
}
```

## Error Responses

### 400 Bad Request
```json
{
  "code": "rest_invalid_param",
  "message": "Invalid parameter(s): title",
  "data": {
    "status": 400,
    "params": {
      "title": "This parameter is required."
    }
  }
}
```

### 404 Not Found
```json
{
  "code": "kh_event_not_found",
  "message": "Event not found.",
  "data": {
    "status": 404
  }
}
```

### 403 Forbidden
```json
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to do that.",
  "data": {
    "status": 403
  }
}
```

## Rate Limiting
- WordPress core rate limiting applies
- Authenticated requests have higher limits
- Public endpoints may have additional restrictions

## Integration Examples

### JavaScript (Fetch API)
```javascript
// Get events
fetch('/wp-json/kh-events/v1/events?per_page=10')
  .then(response => response.json())
  .then(events => console.log(events));

// Create booking
fetch('/wp-json/kh-events/v1/bookings', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    event_id: 123,
    attendee_name: 'John Doe',
    attendee_email: 'john@example.com'
  })
})
.then(response => response.json())
.then(result => console.log(result));
```

### PHP (WordPress)
```php
// Get events
$events = wp_remote_get('/wp-json/kh-events/v1/events');
$events_data = json_decode(wp_remote_retrieve_body($events));

// Create event
$event_data = array(
  'title' => 'New Event',
  'start_date' => '2024-08-01',
  'status' => 'publish'
);

wp_remote_post('/wp-json/kh-events/v1/events', array(
  'body' => json_encode($event_data),
  'headers' => array(
    'Content-Type' => 'application/json',
  ),
));
```

### Python (requests)
```python
import requests

# Get events
response = requests.get('https://example.com/wp-json/kh-events/v1/events')
events = response.json()

# Create booking
booking_data = {
  'event_id': 123,
  'attendee_name': 'John Doe',
  'attendee_email': 'john@example.com'
}

response = requests.post(
  'https://example.com/wp-json/kh-events/v1/bookings',
  json=booking_data
)
result = response.json()
```

## Webhooks and Extensions
The REST API can be extended with custom endpoints and webhooks for:
- Payment processing notifications
- Calendar sync integrations
- Third-party booking systems
- Custom event workflows

## Versioning
- Current version: v1
- Future versions will maintain backward compatibility
- Breaking changes will introduce new version numbers