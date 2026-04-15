<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\Admin\ProjectController as AdminProject;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\HomeController as AdminHome;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\TimeRecordController;
use App\Http\Controllers\Admin\TimeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Admin\Settings\MachineSettingsController;
use App\Http\Controllers\Admin\Settings\MachineController;
use App\Http\Controllers\Admin\Settings\ProjectSettingsController;
use App\Http\Controllers\Admin\Settings\ProjectServicesController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\SupplierOfferController;
use App\Http\Controllers\Admin\SupplierProjectController;
use App\Http\Controllers\Admin\BauteilController;
use App\Http\Controllers\Admin\ProjectOfferController;
use App\Http\Controllers\Admin\Settings\EmailTemplateController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\TablarController;
use App\Http\Controllers\Admin\TablarController as AdminTablarController;

Auth::routes();

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', [HomeController::class, 'index']);

// Project routes
Route::get('/projects/index', [ProjectController::class, 'index'])->name('projects');
Route::get('/projects/logs', [ProjectController::class, 'projectLogs'])->name('projects.logs');
Route::get('/parse-log', [ProjectController::class, 'parseLog'])->name('parse.log');

// Time Recording Routes
Route::prefix('time-records')->name('time-records.')->group(function() {
    Route::get('/', [TimeRecordController::class, 'index'])->name('list');
    Route::get('/create', [TimeRecordController::class, 'create'])->name('create');
    Route::get('/show/{id}', [TimeRecordController::class, 'show'])->name('show');
    Route::get('/change-request/{id}', [TimeRecordController::class, 'changeRequest'])->name('change-request');
    Route::post('/store-change-request/{id}', [TimeRecordController::class, 'storeChangeRequest'])->name('store-change-request');
    Route::post('/store', [TimeRecordController::class, 'store'])->name('store');
    Route::post('/end/{id}', [TimeRecordController::class, 'end'])->name('end');
    Route::post('/switch/{log}', [TimeRecordController::class, 'switch'])->name('switch');
    Route::post('/processes/{process}/end', [TimeRecordController::class, 'endProcess'])->name('processes.end');
});

Route::get('/tablar', [TablarController::class, 'index'])->name('tablar.index');
Route::post('/tablar/consume', [TablarController::class, 'consume'])->name('tablar.consume');

// Language routes
Route::get('/language/{locale}', [LanguageController::class, 'switchLanguage'])->name('language.switch');

// Admin Routes
Route::middleware(['auth', 'role:admin']) 
    ->prefix('admin') 
    ->name('admin.') 
    ->group(function () {
        // Admin Home Views
        Route::get('/emails', [AdminHome::class, 'emails'])->name('emails');
        Route::get('/dashboard', [AdminHome::class, 'index'])->name('dashboard');
        Route::get('/search', [AdminHome::class, 'search'])->name('search');
        Route::post('/notifications/read/{id}', [AdminHome::class, 'markRead'])->name('notifications.read');
        Route::delete('/notifications/{id}', [AdminHome::class, 'deleteNotification'])->name('notifications.delete');

        // Emails management Views
        if (config('modules.emails')) {
            Route::get('/emails', [EmailController::class, 'emails'])->name('emails');
            Route::get('/emails/sent', [EmailController::class, 'emailsSent'])->name('emails.sent');
            Route::get('/emails/show/{id}', [EmailController::class, 'show'])->name('emails.show');
            Route::get('/emails/new', [EmailController::class, 'compose'])->name('emails.new');
            Route::post('/emails/send', [EmailController::class, 'send'])->name('emails.send');
        }

        
        // Projects Routes
        if (config('modules.projects')) {
            Route::get('/projects', [AdminProject::class, 'index'])->name('projects');
            Route::get('/projects/create', [AdminProject::class, 'create'])->name('projects.create');
            Route::post('/projects/store', [AdminProject::class, 'store'])->name('projects.store');
            Route::get('/projects/show/{project}', [AdminProject::class, 'show'])->name('projects.show');
            Route::get('/projects/edit/{project}', [AdminProject::class, 'edit'])->name('projects.edit');
            Route::put('/projects/{project}', [AdminProject::class, 'update'])->name('projects.update');
            Route::delete('/projects/{project}', [AdminProject::class, 'destroy'])->name('projects.destroy');
            Route::get('/bauteile/filter/{type}', [BauteilController::class, 'filter'])->name('bauteile.filter');
            Route::resource('bauteile', BauteilController::class);
            Route::prefix('projects')->name('projects.')->group(function () {

                Route::get('{project}/positions', [PositionController::class, 'index'])->name('positions.index');
                Route::get('{project}/positions/create', [PositionController::class, 'create'])->name('positions.create');
                Route::post('{project}/positions', [PositionController::class, 'store'])->name('positions.store');
                Route::get('{project}/positions/{position}/edit', [PositionController::class, 'edit'])->name('positions.edit');
                Route::put('{project}/positions/{position}', [PositionController::class, 'update'])->name('positions.update');
                Route::delete('{project}/positions/{position}', [PositionController::class, 'destroy'])->name('positions.destroy');
                
                // Supplier offers routes
                Route::get('/offers', [SupplierOfferController::class, 'index'])->name('offers');
                Route::get('/offers/create', [SupplierOfferController::class, 'create'])->name('offers.create');
                Route::post('/offers', [SupplierOfferController::class, 'store'])->name('offers.store');
                Route::get('/offers/{offer}', [SupplierOfferController::class, 'show'])->name('offers.show');
                Route::get('/offers/edit/{offer}', [SupplierOfferController::class, 'edit'])->name('offers.edit');
                Route::put('/offers/{offer}', [SupplierOfferController::class, 'update'])->name('offers.update');
                Route::delete('/offers/{offer}', [SupplierOfferController::class, 'destroy'])->name('offers.destroy');

                // Supplier Projects Routes
                Route::get('/projects', [SupplierProjectController::class, 'index'])->name('projects.index');
                Route::get('/projects/create', [SupplierProjectController::class, 'create'])->name('projects.create');
                Route::post('/projects', [SupplierProjectController::class, 'store'])->name('projects.store');
                Route::get('/projects/{project}', [SupplierProjectController::class, 'show'])->name('projects.show');
                Route::get('/projects/edit/{project}', [SupplierProjectController::class, 'edit'])->name('projects.edit');
                Route::put('/projects/{project}', [SupplierProjectController::class, 'update'])->name('projects.update');
                Route::delete('/projects/{project}', [SupplierProjectController::class, 'destroy'])->name('projects.destroy');
            });
        }

        // Project offers
        if (config('modules.project_offers')) {
            Route::prefix('project_offers')->name('project_offers.')->group(function() {
                Route::resource('', ProjectOfferController::class)->parameters(['' => 'project_offer']);
                // Accept Offer
                Route::get('/{offer}/accept', [ProjectOfferController::class, 'acceptOffer'])->name('accept');
                // Send emails
                Route::get('/{offer}/email-templates', [ProjectOfferController::class, 'emailTemplates'])->name('email-templates');
                Route::get('/{offer}/email-preview/{template?}', [ProjectOfferController::class, 'emailPreview'])->name('email_preview');
                Route::post('/{offer}/send-email', [ProjectOfferController::class, 'sendEmail'])->name('send_email');


                Route::post('/{offer}/add-calculation', [ProjectOfferController::class, 'addCalculation'])->name('add_calculation');
                Route::delete('/{offer}/file/{file}/destroy', [ProjectOfferController::class, 'destroyFile'])->name('files.destroy');
                Route::delete('/{offer}/email/{email}/destroy', [ProjectOfferController::class, 'destroyEmail'])->name('emails.destroy');
                Route::post('/{offer}/add-email', [ProjectOfferController::class, 'addEmail'])->name('add_email');
                Route::get('/email/{email}/edit', [ProjectOfferController::class, 'editEmail'])->name('edit_email');
                Route::put('/email/{email}', [ProjectOfferController::class, 'updateEmail'])->name('update_email');
                Route::post('/{offer}/add-file', [ProjectOfferController::class, 'addFile'])->name('add_file');
                Route::get('/{offer}/calculations', [ProjectOfferController::class, 'calculations'])->name('calculations');
                Route::get('/{offer}/calculation/complete', [ProjectOfferController::class, 'calculationComplete'])->name('calculation.complete');
                Route::get('/{offer}/calculation/pdf', [ProjectOfferController::class, 'calculationPdf'])->name('calculation.pdf');
                Route::get('/{offer}/calculation/show/{calculation}', [ProjectOfferController::class, 'showCalculation'])->name('calculation.show');
                Route::get('/{offer}/items/create', [ProjectOfferController::class, 'createItems'])->name('items.create');
                Route::post('/{offer}/items/store', [ProjectOfferController::class, 'storeItems'])->name('items.store');
                Route::get('/{offer}/items/edit/{calculation}', [ProjectOfferController::class, 'editItems'])->name('items.edit');
                Route::put('/{offer}/items/update{calculation}', [ProjectOfferController::class, 'updateItems'])->name('items.update');
                Route::post('/{offer}/calculations/{calculation}/duplicate', [ProjectOfferController::class, 'duplicateItems'])->name('calculation.duplicate');
                Route::delete('/{offer}/calculation/{calculation}', [ProjectOfferController::class, 'destroyItems'])->name('calculation.destroy');
                Route::get('/children/{parentId}', [ProjectOfferController::class, 'loadChildServices'])->name('children');
            });
        }

        // Users Routes
        if (config('modules.teams')) {
            Route::get('/users', [UserController::class, 'index'])->name('users');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.delete');
            Route::get('/profile', [UserController::class, 'profile'])->name('profile');
            Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
            Route::get('/users/profile/{user}', [UserController::class, 'profile'])->name('users.profile');
            Route::get('/users/edit/{user}', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/update/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('/users/machine-user/toggle/{id}', [UserController::class, 'toggleMachineUser'])->name('machine-user.toggle');
            Route::delete('/users/profile/destroy/{user}', [UserController::class, 'profile'])->name('users.destroy');
        }

        // Time Recording Routes
        if (config('modules.time')) {
            Route::get('/time/logs', [TimeController::class, 'machineLogs'])->name('time.logs');
            Route::get('/time/logs_old', [TimeController::class, 'machineLogsOld'])->name('time.logs_old');
            Route::get('/parse-log', [TimeController::class, 'parseLog'])->name('parse.log');
            Route::get('/time/records', [TimeController::class, 'records'])->name('time.records');
            Route::get('/time/daily-records', [TimeController::class, 'dailyRecords'])->name('time.daily-records');
            Route::get('/time/daily-records/details', [TimeController::class, 'dayDetails']);
            Route::get('/time/records/show/{id}', [TimeController::class, 'show'])->name('time.show');
            Route::get('/time/records/edit/{id}', [TimeController::class, 'editrecord'])->name('time.edit');
            Route::put('/time/records/update/{id?}', [TimeController::class, 'updateRecord'])->name('time.update');
            Route::delete('/time/records/delete/{id}', [TimeController::class, 'deleteRecord'])->name('time.delete');
            Route::get('/time/records/change-logs/{id}', [TimeController::class, 'changeTimeLogs'])->name('time.change-logs');
            Route::post('/time/store-changed-logs/{id}', [TimeController::class, 'storeAndApproveLogs'])->name('time.store-changed-logs');
            Route::post('/time/end/{id}', [TimeController::class, 'end'])->name('time.end');
            Route::post('/time/switch/{log}', [TimeController::class, 'switch'])->name('time.switch');
            Route::get('/time/compare', [TimeController::class, 'compare'])->name('time.compare');
            Route::get('/time/change', [TimeController::class, 'change'])->name('time.change');
            Route::post('/time/change/accept/{id}', [TimeController::class, 'acceptChange'])->name('time.change.accept');
            Route::post('/time/change/reject/{id}', [TimeController::class, 'rejectChange'])->name('time.change.reject');
            Route::get('/time/project/positions', [TimeController::class, 'thisProjectPositions'])->name('time.project.positions');
        }

        // Supplier Routes
        if (config('modules.suppliers')) {
            Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
            Route::get('suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
            Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
            Route::get('suppliers/edit/{supplier}', [SupplierController::class, 'edit'])->name('suppliers.edit');
            Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
            Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
            Route::get('suppliers/show/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
            Route::get('suppliers/projects', [SupplierController::class, 'projects'])->name('suppliers.projects');
        }

        // Projects Routes
        if (config('modules.tablar')) {
            Route::get('/tablar', [AdminTablarController::class, 'index'])->name('tablar.index');
            Route::post('/tablar', [AdminTablarController::class, 'store'])->name('tablar.store');
            Route::put('/tablar/{id}', [AdminTablarController::class, 'update'])->name('tablar.update');
            Route::delete('/tablar/{id}', [AdminTablarController::class, 'destroy'])->name('tablar.destroy');
            Route::get('/tablar/overview', [AdminTablarController::class, 'overview'])->name('tablar.overview');
        }


        if (config('modules.settings')) {
            Route::prefix('settings')->name('settings.')->group(function () {

                // Machine Status
                Route::get('/machine-status', [MachineSettingsController::class, 'machineStatus'])
                    ->name('machine-status');

                Route::get('/machine-status/show/{id?}', [MachineSettingsController::class, 'machineStatusShow'])
                    ->name('machine-status.show');

                Route::post('/machine-status/update/{id?}', [MachineSettingsController::class, 'machineStatusUpdate'])
                    ->name('machine-status.update');

                Route::patch('/machine-status/toggle/{id}', [MachineSettingsController::class, 'toggleMachineStatus'])
                    ->name('machine-status.toggle');

                Route::delete('/machine-status/{id}', [MachineSettingsController::class, 'deleteMachineStatus'])
                    ->name('machine-status.delete');

                // Machines
                Route::get('/machines', [MachineController::class, 'index'])
                    ->name('machines');

                Route::get('/machines/show/{id?}', [MachineController::class, 'show'])
                    ->name('machines.show');

                Route::post('/machines/update/{id?}', [MachineController::class, 'update'])
                    ->name('machines.update');
                
                Route::put('/machines/update/{id}', [MachineController::class, 'update']);

                Route::patch('/machines/toggle/{id}', [MachineController::class, 'toggle'])
                    ->name('machines.toggle');

                Route::delete('/machines/{id}', [MachineController::class, 'delete'])
                    ->name('machines.delete');


                // Project status
                Route::get('/project-status', [ProjectSettingsController::class, 'projectStatus'])->name('project-status');
                Route::get('/project-status/show/{id?}', [ProjectSettingsController::class, 'projectStatusShow'])->name('project-status.show');
                Route::post('/project-status/update/{id?}', [ProjectSettingsController::class, 'projectStatusUpdate'])->name('project-status.update');
                Route::patch('/project-status/toggle/{id}', [ProjectSettingsController::class, 'toggleProjectStatus'])->name('project-status.toggle');
                Route::delete('/project-status/{id}', [ProjectSettingsController::class, 'deleteProjectStatus'])->name('project-status.delete');

                // Project Leistungen
                Route::get('/project-service', [ProjectServicesController::class, 'projectService'])->name('project-service');
                Route::get('/project-service/show/{id?}', [ProjectServicesController::class, 'projectServiceShow'])->name('project-service.show');
                Route::post('/project-service/update/{id?}', [ProjectServicesController::class, 'projectServiceUpdate'])->name('project-service.update');
                Route::patch('/project-service/toggle/{id}', [ProjectServicesController::class, 'toggleProjectService'])->name('project-service.toggle');
                Route::delete('/project-service/{id}', [ProjectServicesController::class, 'deleteProjectService'])->name('project-service.delete');

                // Email Templates
                Route::resource('email_templates', EmailTemplateController::class);
            });
        }

        // Supplier Routes
        if (config('modules.feedback')) {
            Route::get('feedback', [FeedbackController::class, 'index'])->name('feedback.index');
        }
});