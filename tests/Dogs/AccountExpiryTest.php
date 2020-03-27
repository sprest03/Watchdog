<?php

namespace DirectoryTree\Watchdog\Tests\Dogs;

use LdapRecord\Models\Attributes\Timestamp;
use DirectoryTree\Watchdog\LdapNotification;
use LdapRecord\Models\ActiveDirectory\Entry;
use LdapRecord\Laravel\Testing\DirectoryEmulator;
use DirectoryTree\Watchdog\Dogs\WatchAccountExpiry;
use DirectoryTree\Watchdog\Notifications\AccountHasExpired;
use Illuminate\Support\Facades\Notification;

class AccountExpiryTest extends DogTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $model = Entry::class;

        config(["watchdog.watch.$model" => [WatchAccountExpiry::class]]);
        config(['watchdog.attributes.transform' => ['accountexpires' => 'windows-int']]);

        DirectoryEmulator::setup();
    }

    public function test_notification_is_sent()
    {
        Notification::fake();

        $object = Entry::create([
            'cn'            => 'John Doe',
            'objectclass'   => ['foo'],
            'objectguid'    => $this->faker->uuid,
        ]);

        $this->artisan('watchdog:monitor');

        $timestamp = new Timestamp('windows-int');

        $object->update(['accountexpires' => [$timestamp->fromDateTime(now())]]);

        $this->artisan('watchdog:monitor');

        Notification::assertSentTo(app(WatchAccountExpiry::class), AccountHasExpired::class);

        $notification = LdapNotification::where([
            'notification' => AccountHasExpired::class,
            'channels'     => json_encode(['mail']),
        ])->first();

        $this->assertEquals(1, $notification->object_id);
        $this->assertEquals(['mail'], $notification->channels);
        $this->assertEquals(AccountHasExpired::class, $notification->notification);
    }

    public function test_notification_is_not_sent_when_account_has_not_yet_expired()
    {
        Notification::fake();

        $object = Entry::create([
            'cn'            => 'John Doe',
            'objectclass'   => ['foo'],
            'objectguid'    => $this->faker->uuid,
        ]);

        $this->artisan('watchdog:monitor');

        $timestamp = new Timestamp('windows-int');

        $object->update(['accountexpires' => [$timestamp->fromDateTime(now()->addHour())]]);

        $this->artisan('watchdog:monitor');

        Notification::assertNotSentTo(app(WatchAccountExpiry::class), AccountHasExpired::class);
    }

    public function test_notification_is_not_sent_when_a_user_is_already_expired()
    {
        Notification::fake();

        $timestamp = new Timestamp('windows-int');

        Entry::create([
            'cn'                => 'John Doe',
            'objectclass'       => ['foo'],
            'objectguid'        => $this->faker->uuid,
            'accountexpires'    => [$timestamp->fromDateTime(now())]
        ]);

        $this->artisan('watchdog:monitor');

        Notification::assertNotSentTo(app(WatchAccountExpiry::class), AccountHasExpired::class);
    }
}
