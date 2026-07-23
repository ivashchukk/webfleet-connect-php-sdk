<?php

declare(strict_types=1);

namespace Webfleet\Connect\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Webfleet\Connect\Credentials;
use Webfleet\Connect\Query\ObjectQuery;
use Webfleet\Connect\Value\ObjectIdentifier;
use Webfleet\Connect\WebfleetClient;

#[Group('integration')]
final class LiveWebfleetTest extends TestCase
{
    public function testCanRetrieveConfiguredObject(): void
    {
        $objectNumber = getenv('WEBFLEET_CONNECT_TEST_OBJECTNO');
        if (false === $objectNumber || '' === trim($objectNumber)) {
            self::markTestSkipped('WEBFLEET_CONNECT_TEST_OBJECTNO is not configured.');
        }

        $client = WebfleetClient::create(Credentials::fromEnvironment());
        $objects = $client->fleetObjects(new ObjectQuery(ObjectIdentifier::number($objectNumber)));

        self::assertNotEmpty($objects);
        self::assertSame($objectNumber, $objects[0]->number);
    }
}
