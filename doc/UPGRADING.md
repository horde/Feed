# Upgrading to Modern Feed API (src/)

## Overview

Feed 3.0 introduces a modern PSR-4 implementation using the new Horde\Xml\Element\ API and PSR-7/18 HTTP. Both APIs coexist until 4.0.

## Old API (lib/)

```php
$feed = Horde_Feed::readUri('http://example.com/feed.xml');
foreach ($feed as $entry) {
    echo $entry->title();
}
```

## New API (src/)

```php
use Horde\Feed\Feed;

$feed = Feed::readUri('http://example.com/feed.xml');
foreach ($feed as $entry) {
    echo $entry->title();
}
```

## Key Differences

### Class Names

| Old (lib/)                  | New (src/)                    |
|-----------------------------|-------------------------------|
| `Horde_Feed`                | `Horde\Feed\Feed`             |
| `Horde_Feed_Atom`           | `Horde\Feed\Atom`             |
| `Horde_Feed_Rss`            | `Horde\Feed\Rss`              |
| `Horde_Feed_Base`           | `Horde\Feed\FeedBase`         |
| `Horde_Feed_Entry_Base`     | `Horde\Feed\EntryBase`        |
| `Horde_Feed_Entry_Atom`     | `Horde\Feed\AtomEntry`        |
| `Horde_Feed_Entry_Rss`      | `Horde\Feed\RssEntry`         |
| `Horde_Feed_Blogroll`       | `Horde\Feed\Blogroll`         |
| `Horde_Feed_Entry_Blogroll` | `Horde\Feed\BlogrollEntry`    |
| `Horde_Feed_Exception`      | `Horde\Feed\Exception`        |

### HTTP Client Injection

The new API uses PSR-18 `ClientInterface` instead of `Horde_Http_Client`:

```php
// Old (lib/)
use Horde_Http_Client;
$client = new Horde_Http_Client();
$feed = Horde_Feed::readUri($uri, $client);

// New (src/)
use Horde\Http\Client\Curl;
use Horde\Http\Client\Options;
use Horde\Http\ResponseFactory;
use Horde\Http\StreamFactory;
use Psr\Http\Client\ClientInterface;

// Create a default client (or use a clientFactory)
$client = new Curl(
    new ResponseFactory(),
    new StreamFactory(),
    new Options()
);
$feed = Feed::readUri($uri, $client);

// Or use any PSR-18 compatible client
$feed = Feed::readUri($uri, $customClient);
```

**Key changes:**
- Method: `_buildListItemCache()` → `buildListItemCache()`
- Visibility: `protected` → `public`
- Return type: add `: array`
- Property: `$this->_element` → `$this->element`

### Subclassing EntryBase

If you extend EntryBase or create custom entry types:

```php
// Old (lib/)
class MyEntry extends Horde_Feed_Entry_Base
{
    protected $_emptyXml = '<myentry/>';
}

// New (src/)
use Horde\Feed\EntryBase;

class MyEntry extends EntryBase
{
    protected string $emptyXml = '<myentry/>';
}
```

**Key changes:**
- Property: `$_emptyXml` → `$emptyXml`
- Type declaration: `string`

### Property Access (Internal)

When extending the renewed classes observe the dropped underscore in the variable names.

| Old (lib/)           | New (src/)          |
|----------------------|---------------------|
| `$feed->_uri`        | `$feed->uri`        |
| `$feed->_httpClient` | `$feed->httpClient` |
| `$entry->_element`   | `$entry->element`   |
| `$entry->_children`  | `$entry->children`  |

Consider using the public interface methods instead:

```php
// New (src/)
$uri = $feed->getUri();
$client = $feed->getHttpClient();
```

### Atom Publishing Protocol (APP)

The `save()` and `delete()` methods in Horde\Feed\AtomEntry now use PSR-7 Request/Response internally:

```php
use Horde\Feed\AtomEntry;
use Psr\Http\Client\ClientInterface;

$entry = new AtomEntry(null, $httpClient);
$entry->title = 'New Entry';
$entry->content = 'Entry content';

// Create new entry
$entry->save('http://example.com/feed');

// Update existing entry
$entry->content = 'Updated content';
$entry->save();

// Delete entry
$entry->delete();
```

The public API is semantically unchanged.
However, all methods and return values are now properly typed and Horde_Http_Client has been replaced by Horde\Http\ (PSR-7).

## Why Upgrade?

1. **Modern XML:** Uses Horde\Xml\Element\ API with strict types
2. **Future-Proof:** lib/ will be removed in 4.0.0
3. **Interface:** Use FeedInterface for type hinting feed objects
4. **Standards Compliance:** PSR-18 HTTP client, PSR-7 messages

## Migration Timeline

- **3.0.0:** Both APIs available (lib/ and src/)
- **3.x:** lib/ "made to run" maintained for backward compatibility but officially deprecated.
- **4.0.0:** lib/ removed, src/ only
