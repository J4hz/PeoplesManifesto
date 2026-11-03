<?php

use App\Jobs\CheckDoneClusters;
use App\Jobs\DispatchWeeklyBriefs;
use App\Jobs\PollBriefBatches;
use App\Jobs\RerankOpenClusters;
use Illuminate\Support\Facades\Schedule;

// Times are Africa/Nairobi (config/app.php).

Schedule::job(new CheckDoneClusters)->dailyAt('06:15')->name('reopen-check')->onOneServer();
Schedule::job(new RerankOpenClusters)->hourly()->name('rerank')->onOneServer();
Schedule::job(new DispatchWeeklyBriefs)->weeklyOn(1, '05:30')->name('weekly-briefs')->onOneServer();
Schedule::job(new PollBriefBatches)->everyFifteenMinutes()->name('poll-brief-batches')->onOneServer();

Schedule::command('backup:clean')->dailyAt('01:00')->onOneServer();
Schedule::command('backup:run')->dailyAt('01:30')->onOneServer();
Schedule::command('backup:monitor')->dailyAt('07:00')->onOneServer();

Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::command('model:prune')->dailyAt('02:30')->onOneServer();
