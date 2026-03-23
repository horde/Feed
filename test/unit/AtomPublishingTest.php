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
use Horde\Http\Client\Curl;
use Horde\Http\Client\Options;
use Horde\Http\Response;
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
class AtomPublishingTest extends TestCase
{
    public function testSaveNewEntry(): void
    {
        $mockClient = $this->createMockClient(201, '<?xml version="1.0"?><entry xmlns="http://www.w3.org/2005/Atom"><id>new-id</id><title>Created</title></entry>');

        $entry = new AtomEntry(null, $mockClient);
        $entry->title = 'Test Entry';
        $entry->content = 'Test content';

        $entry->save('http://example.com/feed');
        $this->assertEquals('new-id', $entry->id());
    }

    public function testSaveNewEntryWithoutPostUri(): void
    {
        $mockClient = $this->createMockClient(201, '<entry/>');

        $entry = new AtomEntry(null, $mockClient);
        $entry->title = 'Test Entry';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('PostURI must be specified');
        $entry->save();
    }

    public function testSaveExistingEntry(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<entry xmlns="http://www.w3.org/2005/Atom">
    <id>existing-id</id>
    <title>Original Title</title>
    <link rel="edit" href="http://example.com/entry/1"/>
</entry>';

        $mockClient = $this->createMockClient(200, '<?xml version="1.0"?><entry xmlns="http://www.w3.org/2005/Atom"><id>existing-id</id><title>Updated Title</title></entry>');

        $entry = new AtomEntry($xml, $mockClient);
        $entry->title = 'Updated Title';
        $entry->save();

        $this->assertEquals('Updated Title', $entry->title());
    }

    public function testDeleteEntry(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<entry xmlns="http://www.w3.org/2005/Atom">
    <id>existing-id</id>
    <title>To Delete</title>
    <link rel="edit" href="http://example.com/entry/1"/>
</entry>';

        $mockClient = $this->createMockClient(200, '');

        $entry = new AtomEntry($xml, $mockClient);
        $result = $entry->delete();

        $this->assertTrue($result);
    }

    public function testDeleteEntryWithoutEditLink(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<entry xmlns="http://www.w3.org/2005/Atom">
    <id>existing-id</id>
    <title>No Edit Link</title>
</entry>';

        $mockClient = $this->createMockClient(200, '');

        $entry = new AtomEntry($xml, $mockClient);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('no link rel="edit"');
        $entry->delete();
    }

    public function testDeleteFollowsRedirect(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<entry xmlns="http://www.w3.org/2005/Atom">
    <id>existing-id</id>
    <title>Redirects</title>
    <link rel="edit" href="http://example.com/entry/1"/>
</entry>';

        $redirectCount = 0;
        $mockClient = new class ($redirectCount) implements ClientInterface {
            private int $callCount = 0;

            public function __construct(private int &$redirectCount) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->callCount++;
                $this->redirectCount = $this->callCount;

                if ($this->callCount === 1) {
                    // First call: redirect
                    $factory = new ResponseFactory();
                    $streamFactory = new StreamFactory();
                    return $factory->createResponse(302)
                        ->withHeader('Location', 'http://example.com/entry/1/moved')
                        ->withBody($streamFactory->createStream(''));
                }
                // Second call: success
                $factory = new ResponseFactory();
                $streamFactory = new StreamFactory();
                return $factory->createResponse(200)
                    ->withBody($streamFactory->createStream(''));
            }
        };

        $entry = new AtomEntry($xml, $mockClient);
        $result = $entry->delete();

        $this->assertTrue($result);
        $this->assertEquals(2, $redirectCount, 'Should follow redirect');
    }

    public function testSaveNewEntryNon201ResponseThrows(): void
    {
        $mockClient = $this->createMockClient(400, '');

        $entry = new AtomEntry(null, $mockClient);
        $entry->title = 'Test Entry';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Expected 2xx response code, got 400');
        $entry->save('http://example.com/feed');
    }

    public function testSaveExistingEntryNon200ResponseThrows(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<entry xmlns="http://www.w3.org/2005/Atom">
    <id>existing-id</id>
    <title>Original</title>
    <link rel="edit" href="http://example.com/entry/1"/>
</entry>';

        $mockClient = $this->createMockClient(404, '');

        $entry = new AtomEntry($xml, $mockClient);
        $entry->title = 'Updated';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Expected 2xx response code, got 404');
        $entry->save();
    }

    private function createMockClient(int $statusCode, string $body): ClientInterface
    {
        return new class ($statusCode, $body) implements ClientInterface {
            public function __construct(
                private int $statusCode,
                private string $body
            ) {}

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $factory = new ResponseFactory();
                $streamFactory = new StreamFactory();
                return $factory->createResponse($this->statusCode)
                    ->withBody($streamFactory->createStream($this->body));
            }
        };
    }
}
