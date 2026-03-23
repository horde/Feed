<?php

declare(strict_types=1);

/**
 * Copyright 2007-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Feed\Test;

use Horde\Feed\AtomEntry;
use Horde\Feed\Exception;
use Horde\Http\ResponseFactory;
use Horde\Http\StreamFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Feed
 * @subpackage UnitTests
 */
#[CoversClass(AtomEntry::class)]
class AtomETagTest extends TestCase
{
    public function testSaveWithETagSendsIfMatchHeader(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<entry xmlns="http://www.w3.org/2005/Atom">
    <id>existing-id</id>
    <title>Original</title>
    <link rel="edit" href="http://example.com/entry/1"/>
</entry>';

        $capturedHeaders = [];
        $mockClient = new class ($capturedHeaders) implements ClientInterface {
            public function __construct(private array &$headers) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                // Capture headers
                foreach ($request->getHeaders() as $name => $values) {
                    $this->headers[$name] = $values;
                }

                $factory = new ResponseFactory();
                $streamFactory = new StreamFactory();
                return $factory->createResponse(200)
                    ->withBody($streamFactory->createStream('<?xml version="1.0"?><entry xmlns="http://www.w3.org/2005/Atom"><id>existing-id</id></entry>'));
            }
        };

        $entry = new AtomEntry($xml, $mockClient);
        $entry->title = 'Updated';
        $entry->save(etag: '"version-123"');

        $this->assertArrayHasKey('If-Match', $capturedHeaders);
        $this->assertEquals(['"version-123"'], $capturedHeaders['If-Match']);
    }

    public function testSaveWithSlugSendsSlugHeader(): void
    {
        $capturedHeaders = [];
        $mockClient = new class ($capturedHeaders) implements ClientInterface {
            public function __construct(private array &$headers) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                // Capture headers
                foreach ($request->getHeaders() as $name => $values) {
                    $this->headers[$name] = $values;
                }

                $factory = new ResponseFactory();
                $streamFactory = new StreamFactory();
                return $factory->createResponse(201)
                    ->withBody($streamFactory->createStream('<?xml version="1.0"?><entry xmlns="http://www.w3.org/2005/Atom"><id>new-id</id></entry>'));
            }
        };

        $entry = new AtomEntry(null, $mockClient);
        $entry->title = 'New Entry';
        $entry->save('http://example.com/feed', slug: 'my-entry');

        $this->assertArrayHasKey('Slug', $capturedHeaders);
        $this->assertEquals(['my-entry'], $capturedHeaders['Slug']);
    }

    public function testGetEtagReturnsStoredValue(): void
    {
        $mockClient = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $factory = new ResponseFactory();
                $streamFactory = new StreamFactory();
                return $factory->createResponse(201)
                    ->withHeader('ETag', '"version-456"')
                    ->withBody($streamFactory->createStream('<?xml version="1.0"?><entry xmlns="http://www.w3.org/2005/Atom"><id>new-id</id></entry>'));
            }
        };

        $entry = new AtomEntry(null, $mockClient);
        $entry->title = 'Test';
        $this->assertNull($entry->getEtag());

        $entry->save('http://example.com/feed');
        $this->assertEquals('"version-456"', $entry->getEtag());
    }

    public function testETagStoredFromPutResponse(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<entry xmlns="http://www.w3.org/2005/Atom">
    <id>existing-id</id>
    <title>Original</title>
    <link rel="edit" href="http://example.com/entry/1"/>
</entry>';

        $mockClient = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $factory = new ResponseFactory();
                $streamFactory = new StreamFactory();
                return $factory->createResponse(200)
                    ->withHeader('ETag', '"updated-etag"')
                    ->withBody($streamFactory->createStream('<?xml version="1.0"?><entry xmlns="http://www.w3.org/2005/Atom"><id>existing-id</id></entry>'));
            }
        };

        $entry = new AtomEntry($xml, $mockClient);
        $entry->title = 'Updated';
        $entry->save();

        $this->assertEquals('"updated-etag"', $entry->getEtag());
    }

    public function testContentTypeHeaderIncludesTypeParameter(): void
    {
        $capturedHeaders = [];
        $mockClient = new class ($capturedHeaders) implements ClientInterface {
            public function __construct(private array &$headers) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                foreach ($request->getHeaders() as $name => $values) {
                    $this->headers[$name] = $values;
                }

                $factory = new ResponseFactory();
                $streamFactory = new StreamFactory();
                return $factory->createResponse(201)
                    ->withBody($streamFactory->createStream('<?xml version="1.0"?><entry xmlns="http://www.w3.org/2005/Atom"><id>new-id</id></entry>'));
            }
        };

        $entry = new AtomEntry(null, $mockClient);
        $entry->title = 'Test';
        $entry->save('http://example.com/feed');

        $this->assertArrayHasKey('Content-Type', $capturedHeaders);
        $this->assertEquals(['application/atom+xml;type=entry'], $capturedHeaders['Content-Type']);
    }

    public function testAccepts202StatusCode(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<entry xmlns="http://www.w3.org/2005/Atom">
    <id>existing-id</id>
    <title>Original</title>
    <link rel="edit" href="http://example.com/entry/1"/>
</entry>';

        $mockClient = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $factory = new ResponseFactory();
                $streamFactory = new StreamFactory();
                return $factory->createResponse(202)  // Accepted
                    ->withBody($streamFactory->createStream(''));
            }
        };

        $entry = new AtomEntry($xml, $mockClient);
        $entry->title = 'Updated';
        $entry->save();  // Should not throw

        $this->assertTrue(true);  // If we get here, 202 was accepted
    }

    public function testAccepts204StatusCode(): void
    {
        $mockClient = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $factory = new ResponseFactory();
                $streamFactory = new StreamFactory();
                return $factory->createResponse(204)  // No Content
                    ->withBody($streamFactory->createStream(''));
            }
        };

        $entry = new AtomEntry(null, $mockClient);
        $entry->title = 'Test';
        $entry->save('http://example.com/feed');  // Should not throw

        $this->assertTrue(true);  // If we get here, 204 was accepted
    }

    public function testRejects4xxStatusCode(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<entry xmlns="http://www.w3.org/2005/Atom">
    <id>existing-id</id>
    <title>Original</title>
    <link rel="edit" href="http://example.com/entry/1"/>
</entry>';

        $mockClient = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $factory = new ResponseFactory();
                $streamFactory = new StreamFactory();
                return $factory->createResponse(412)  // Precondition Failed
                    ->withBody($streamFactory->createStream(''));
            }
        };

        $entry = new AtomEntry($xml, $mockClient);
        $entry->title = 'Updated';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Expected 2xx response code, got 412');
        $entry->save(etag: '"old-version"');
    }
}
