<?php

namespace DutchBridge\KlaviyoForLaravel\Test\Feature;

use DutchBridge\KlaviyoForLaravel\Jobs\SendKlaviyoTrack;
use DutchBridge\KlaviyoForLaravel\Test\TestCase;
use DutchBridge\KlaviyoForLaravel\TrackEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_track_job_when_tracking_events()
    {
        Queue::fake();
        
        $event = new TrackEvent('Test Event', ['key' => 'value'], ['email' => 'test@example.com']);
        
        app('klaviyo')->track($event);
        
        Queue::assertPushed(SendKlaviyoTrack::class, function ($job) {
            return count($job->events) === 1;
        });
    }

    public function test_it_does_not_dispatch_job_when_disabled()
    {
        Queue::fake();
        
        app('klaviyo')->disable();
        
        $event = new TrackEvent('Test Event', ['key' => 'value'], ['email' => 'test@example.com']);
        
        app('klaviyo')->track($event);
        
        Queue::assertNotPushed(SendKlaviyoTrack::class);
    }

    public function test_it_rejects_events_without_identity()
    {
        Queue::fake();
        
        $event = new TrackEvent('Test Event', ['key' => 'value'], []);
        
        app('klaviyo')->track($event);
        
        Queue::assertNotPushed(SendKlaviyoTrack::class);
    }

    public function test_track_job_sends_http_requests()
    {
        Http::fake([
            '*/events' => Http::response(['data' => ['id' => '123']], 200),
        ]);

        $event = new TrackEvent('Test Event', ['key' => 'value'], ['email' => 'test@example.com']);
        $job = new SendKlaviyoTrack($event);
        
        $job->handle(app('klaviyo'));
        
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://a.klaviyo.com/api/events' &&
                   $request->hasHeader('Authorization', 'Klaviyo-API-Key test-private-key') &&
                   $request->hasHeader('revision', '2024-05-15') &&
                   $request->header('Content-Type')[0] === 'application/json';
        });
    }

    public function test_it_can_track_multiple_events()
    {
        Queue::fake();
        
        $event1 = new TrackEvent('Event 1', ['key1' => 'value1'], ['email' => 'test1@example.com']);
        $event2 = new TrackEvent('Event 2', ['key2' => 'value2'], ['email' => 'test2@example.com']);
        
        app('klaviyo')->track($event1, $event2);
        
        Queue::assertPushed(SendKlaviyoTrack::class, function ($job) {
            return count($job->events) === 2;
        });
    }
}