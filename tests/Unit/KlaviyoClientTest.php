<?php

namespace DutchBridge\KlaviyoForLaravel\Test\Unit;

use DutchBridge\KlaviyoForLaravel\KlaviyoClient;
use DutchBridge\KlaviyoForLaravel\Test\TestCase;
use DutchBridge\KlaviyoForLaravel\TrackEvent;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class KlaviyoClientTest extends TestCase
{
    private KlaviyoClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = new KlaviyoClient([
            'enabled' => true,
            'endpoint' => 'https://a.klaviyo.com/api/',
            'private_api_key' => 'test-private-key',
            'public_api_key' => 'test-public-key',
            'api_version' => '2024-05-15',
            'identity_key_name' => 'email',
        ]);
    }

    public function test_it_can_be_instantiated_with_config()
    {
        $this->assertInstanceOf(KlaviyoClient::class, $this->client);
        $this->assertEquals('https://a.klaviyo.com/api/', $this->client->getEndpoint());
        $this->assertEquals('test-private-key', $this->client->getPrivateKey());
        $this->assertEquals('test-public-key', $this->client->getPublicKey());
        $this->assertEquals('2024-05-15', $this->client->getApiVersion());
        $this->assertEquals('email', $this->client->getIdentityKeyName());
        $this->assertTrue($this->client->isEnabled());
    }

    public function test_it_throws_exception_for_invalid_api_version()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid API Version');

        new KlaviyoClient([
            'enabled' => true,
            'endpoint' => 'https://a.klaviyo.com/api/',
            'private_api_key' => 'test-private-key',
            'public_api_key' => 'test-public-key',
            'api_version' => null,
            'identity_key_name' => 'email',
        ]);
    }

    public function test_it_throws_exception_for_invalid_identity_key_name()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid default identity key name');

        new KlaviyoClient([
            'enabled' => true,
            'endpoint' => 'https://a.klaviyo.com/api/',
            'private_api_key' => 'test-private-key',
            'public_api_key' => 'test-public-key',
            'api_version' => '2024-05-15',
            'identity_key_name' => null,
        ]);
    }

    public function test_it_can_enable_and_disable_client()
    {
        $this->assertTrue($this->client->isEnabled());
        
        $this->client->disable();
        $this->assertFalse($this->client->isEnabled());
        
        $this->client->enable();
        $this->assertTrue($this->client->isEnabled());
    }

    public function test_it_can_resolve_identity_from_array()
    {
        $identity = $this->client->resolveIdentity(['email' => 'test@example.com']);
        $this->assertEquals(['email' => 'test@example.com'], $identity);
    }

    public function test_it_can_resolve_identity_from_string()
    {
        $identity = $this->client->resolveIdentity('test@example.com');
        $this->assertEquals(['email' => 'test@example.com'], $identity);
    }

    public function test_it_throws_exception_for_invalid_identity()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identify requires one of the following fields: email, id, phone_number, _kx');

        $this->client->resolveIdentity(['invalid_field' => 'value']);
    }

    public function test_it_can_push_events_to_collection()
    {
        $this->client->push('track', 'Test Event', ['key' => 'value']);
        
        $collection = $this->client->getPushCollection();
        $this->assertCount(1, $collection);
        $this->assertEquals(['track', 'Test Event', ['key' => 'value']], $collection->first());
    }

    public function test_it_throws_exception_for_invalid_push_arguments()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not enough arguments for push.');

        $this->client->push();
    }

    public function test_it_throws_exception_for_too_many_push_arguments()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Too many arguments for push.');

        $this->client->push('track', 'Event', ['data'], 'extra');
    }

    public function test_it_can_prepend_events_to_collection()
    {
        $this->client->push('track', 'Second Event');
        $this->client->prepend('track', 'First Event');
        
        $collection = $this->client->getPushCollection();
        $this->assertCount(2, $collection);
        $this->assertEquals(['track', 'First Event'], $collection->first());
    }

    public function test_track_does_nothing_when_disabled()
    {
        $this->client->disable();
        
        Http::fake();
        
        $event = new TrackEvent('Test Event', [], ['email' => 'test@example.com']);
        $this->client->track($event);
        
        Http::assertNothingSent();
    }
}