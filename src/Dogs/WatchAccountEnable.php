<?php

namespace DirectoryTree\Watchdog\Dogs;

use DirectoryTree\Watchdog\Watchdog;
use DirectoryTree\Watchdog\Notifications\AccountHasBeenEnabled;
use DirectoryTree\Watchdog\Conditions\ActiveDirectory\AccountEnabled;

class WatchAccountEnable extends Watchdog
{
    protected $conditions = [AccountEnabled::class];

    public function getName()
    {
        return trans('watchdog::watchdogs.accounts_enabled');
    }

    public function getKey()
    {
        return 'watchdog.accounts.enabled';
    }

    public function getNotifiableSubject()
    {
        return "Account [{$this->object->name}] has been enabled";
    }

    public function notification()
    {
        return AccountHasBeenEnabled::class;
    }
}
