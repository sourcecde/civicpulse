<?php

    use App\Http\Controllers\Auth\LoginController;
    use App\Http\Controllers\Auth\LogoutController;
    use App\Http\Controllers\Auth\MeController;
    use App\Http\Controllers\Auth\RegisterController;
    use App\Http\Controllers\HealthController;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\CategoryController;
    use App\Http\Controllers\ReportController;
    use App\Http\Controllers\Api\ReportWorkflowController;
    use App\Http\Controllers\Api\DashboardController;

    Route::get('/health', HealthController::class);

    Route::post('/register', RegisterController::class);
    Route::post('/login', LoginController::class)->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', MeController::class);
        Route::post('/logout', LogoutController::class);

        // ReportWorkflow routes //
        Route::post('/reports/{report}/assign', [ReportWorkflowController::class, 'assign']);
        Route::patch('/reports/{report}/status', [ReportWorkflowController::class, 'updateStatus']);
        Route::get('/reports/{report}/history', [ReportWorkflowController::class, 'history']);

        // Operator dashboard routes //
        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    });

    // Category routes //
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'delete']);


    // Report routes //
        // Public report submission
            Route::post('/reports', [ReportController::class, 'store']);
            Route::post('/guest/reports/{trackingNumber}/lookup',
                [ReportController::class, 'guestShow',])->middleware('throttle:10,1');
        // Protected report operations
            Route::middleware('auth:sanctum')->group(function () {
            Route::get('/reports', [ReportController::class, 'index']);
            Route::get('/reports/{id}', [ReportController::class, 'show']);
            Route::patch('/reports/{id}', [ReportController::class, 'update']);
        });

    // Operator routes //
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/operators', [\App\Http\Controllers\OperatorController::class, 'index']);
    });
