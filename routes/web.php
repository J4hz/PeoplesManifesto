<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Citizen;
use App\Http\Controllers\Leader;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\Public;
use App\Http\Controllers\StaffSecurityController;
use App\Http\Controllers\Webhooks\AfricasTalkingController;
use Illuminate\Support\Facades\Route;

/*
| Public pages. Anonymous, aggregated data only.
*/
Route::get('/', Citizen\HomeController::class)->name('home');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::post('/language', LanguageController::class)->name('language');
Route::get('/wards/{ward}', [Public\WardController::class, 'show'])->name('wards.show');
Route::get('/issues/{cluster}', [Public\ClusterController::class, 'show'])->name('clusters.show');
Route::get('/issues/{cluster}/fixed-count', [Public\ClusterController::class, 'fixedCount'])->name('clusters.fixed-count');
Route::get('/leaders/{leader}', [Public\LeaderRecordController::class, 'show'])->name('leaders.show');
Route::get('/photos/updates/{update}', [PhotoController::class, 'update'])->name('photos.update');

/*
| Residents: phone number and SMS code.
*/
Route::middleware('guest:citizen')->group(function () {
    Route::get('/sign-in', [Citizen\SignInController::class, 'create'])->name('citizen.sign-in');
    Route::post('/sign-in', [Citizen\SignInController::class, 'store'])->middleware('throttle:otp');
    Route::get('/sign-in/code', [Citizen\SignInController::class, 'verifyForm'])->name('citizen.sign-in.verify');
    Route::post('/sign-in/code', [Citizen\SignInController::class, 'verify'])->middleware('throttle:otp-verify');
});

Route::middleware('auth:citizen')->group(function () {
    Route::post('/sign-out', [Citizen\SignInController::class, 'destroy'])->name('citizen.sign-out');
    Route::get('/welcome/ward', [Citizen\WardChoiceController::class, 'create'])->name('citizen.ward.choose');
    Route::post('/welcome/ward', [Citizen\WardChoiceController::class, 'store']);

    Route::middleware('citizen.ward')->group(function () {
        Route::post('/reports', [Citizen\SubmissionController::class, 'store'])->middleware('throttle:submissions')->name('citizen.submissions.store');
        Route::get('/my-ward', [Citizen\MyWardController::class, 'show'])->name('citizen.ward');
        Route::post('/issues/{cluster}/voice', [Citizen\VoiceController::class, 'store'])->middleware('throttle:voices')->name('citizen.voice');
        Route::get('/issues/{cluster}/rate', [Citizen\RatingController::class, 'create'])->name('citizen.rate');
        Route::post('/issues/{cluster}/rate', [Citizen\RatingController::class, 'store'])->middleware('throttle:ratings')->name('citizen.rate.store');
        Route::get('/my-reports', [Citizen\MyReportsController::class, 'index'])->name('citizen.reports');
        Route::get('/my-reports/{submission}/photo', [PhotoController::class, 'submission'])->name('citizen.reports.photo');
        Route::get('/account', [Citizen\AccountController::class, 'edit'])->name('citizen.account');
        Route::put('/account', [Citizen\AccountController::class, 'update'])->name('citizen.account.update');
        Route::delete('/account', [Citizen\AccountController::class, 'destroy'])->name('citizen.account.destroy');
    });
});

/*
| Leaders, leader staff and admins. Fortify registers /staff/login and friends.
*/
Route::middleware('auth:web')->group(function () {
    Route::get('/staff/security', [StaffSecurityController::class, 'show'])->name('staff.security');
});

Route::middleware(['auth:web', 'two-factor', 'can:leader-dashboard'])->prefix('dashboard')->name('dashboard')->group(function () {
    Route::get('/', [Leader\OverviewController::class, 'index']);
    Route::get('/issues/{cluster}', [Leader\RespondController::class, 'show'])->name('.clusters.show');
    Route::post('/issues/{cluster}/updates', [Leader\UpdateController::class, 'store'])->name('.updates.store');
    Route::post('/updates/{update}/publish', [Leader\UpdateController::class, 'publish'])->name('.updates.publish');
    Route::delete('/updates/{update}', [Leader\UpdateController::class, 'destroy'])->name('.updates.destroy');
    Route::get('/submissions/{submission}/photo', [PhotoController::class, 'submission'])->name('.submissions.photo');
    Route::get('/ratings', [Leader\RatingRecordController::class, 'index'])->name('.ratings');
    Route::get('/audit', [Leader\AuditLogController::class, 'index'])->name('.audit');
});

Route::middleware(['auth:web', 'two-factor', 'can:admin'])->prefix('admin')->name('admin')->group(function () {
    Route::get('/', [Admin\OverviewController::class, 'index']);
    Route::get('/wards', [Admin\WardController::class, 'index'])->name('.wards');
    Route::post('/wards/{ward}/areas', [Admin\WardController::class, 'storeArea'])->name('.wards.areas.store');
    Route::patch('/wards/{ward}', [Admin\WardController::class, 'update'])->name('.wards.update');
    Route::get('/people', [Admin\PeopleController::class, 'index'])->name('.people');
    Route::post('/people/leaders', [Admin\PeopleController::class, 'storeLeader'])->name('.people.leaders.store');
    Route::post('/people/staff', [Admin\PeopleController::class, 'storeStaff'])->name('.people.staff.store');
    Route::patch('/people/{user}/active', [Admin\PeopleController::class, 'toggleActive'])->name('.people.active');
    Route::get('/intake', [Admin\IntakeController::class, 'index'])->name('.intake');
    Route::post('/intake/{submission}/retry', [Admin\IntakeController::class, 'retry'])->name('.intake.retry');
    Route::get('/ai-usage', [Admin\AiUsageController::class, 'index'])->name('.ai-usage');
    Route::get('/audit', [Admin\AuditLogController::class, 'index'])->name('.audit');
});

/*
| Africa's Talking callbacks. Verified by a secret in the URL, see the controller.
*/
Route::post('/webhooks/africastalking/{token}/sms', [AfricasTalkingController::class, 'sms'])
    ->middleware('throttle:sms-inbound')
    ->name('webhooks.africastalking.sms');
