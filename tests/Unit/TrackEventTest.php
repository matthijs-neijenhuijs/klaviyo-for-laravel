<?php

namespace DutchBridge\KlaviyoForLaravel\Test\Unit;

use DutchBridge\KlaviyoForLaravel\Test\TestCase;
use DutchBridge\KlaviyoForLaravel\TrackEvent;
use Illuminate\Support\Carbon;

class TrackEventTest extends TestCase
{
    public function test_it_can_be_created_with_required_parameters()
    {
        $event = new TrackEvent('Test Event', [], ['email' => 'test@example.com']);
        
        $this->assertEquals('Test Event', $event->metric_name);
        $this->assertEquals([], $event->payload);
        $this->assertEquals(['email' => 'test@example.com'], $event->identity);
        $this->assertInstanceOf(Carbon::class, $event->timestamp);
    }

    public function test_it_can_be_created_with_make_method()
    {
        $timestamp = Carbon::now();
        $event = TrackEvent::make('Test Event', ['key' => 'value'], ['email' => 'test@example.com'], $timestamp);
        
        $this->assertEquals('Test Event', $event->metric_name);
        $this->assertEquals(['key' => 'value'], $event->payload);
        $this->assertEquals(['email' => 'test@example.com'], $event->identity);
        $this->assertEquals($timestamp, $event->timestamp);
    }

    public function test_it_has_unique_id()
    {
        $event1 = new TrackEvent('Test Event', [], ['email' => 'test@example.com']);
        $event2 = new TrackEvent('Test Event', [], ['email' => 'test@example.com']);
        
        $this->assertNotEquals($event1->id, $event2->id);
        $this->assertIsString($event1->id);
        $this->assertIsString($event2->id);
    }

    public function test_it_can_get_and_set_event_name()
    {
        $event = new TrackEvent('Original Event', [], ['email' => 'test@example.com']);
        
        $this->assertEquals('Original Event', $event->getEvent());
        
        $event->setEvent('Updated Event');
        $this->assertEquals('Updated Event', $event->getEvent());
        $this->assertEquals('Updated Event', $event->metric_name);
    }

    public function test_it_can_get_and_set_properties()
    {
        $event = new TrackEvent('Test Event', ['original' => 'value'], ['email' => 'test@example.com']);
        
        $this->assertEquals(['original' => 'value'], $event->getProperties());
        
        $event->setProperties(['updated' => 'value']);
        $this->assertEquals(['updated' => 'value'], $event->getProperties());
        $this->assertEquals(['updated' => 'value'], $event->payload);
    }

    public function test_it_can_set_null_properties()
    {
        $event = new TrackEvent('Test Event', ['original' => 'value'], ['email' => 'test@example.com']);
        
        $event->setProperties(null);
        $this->assertNull($event->getProperties());
        $this->assertNull($event->payload);
    }

    public function test_it_uses_current_time_when_no_timestamp_provided()
    {
        $before = Carbon::now();
        $event = new TrackEvent('Test Event', [], ['email' => 'test@example.com']);
        $after = Carbon::now();
        
        $this->assertTrue($event->timestamp->between($before, $after));
    }
}