<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class MaintenanceController extends Controller
{
    private Application $app;
    private Filesystem $files;

    public function __construct(Application $app, Filesystem $files)
    {
        $this->app = $app;
        $this->files = $files;
    }

    public function optimizeClear(): Response
    {
        try {
            $messages = [];

            $this->files->delete($this->app->getCachedEventsPath());
            $messages[] = 'Cached events cleared!';

            $this->clearCompiledViews();
            $messages[] = 'Compiled views cleared!';

            if (! Cache::flush()) {
                return $this->plainTextResponse(
                    'Optimize clear failed.' . "\n" . 'Application cache could not be cleared. Check storage/cache permissions.',
                    500
                );
            }
            $this->flushFacades();
            $messages[] = 'Application cache cleared!';

            $this->files->delete($this->app->getCachedRoutesPath());
            $messages[] = 'Route cache cleared!';

            $this->files->delete($this->app->getCachedConfigPath());
            $messages[] = 'Configuration cache cleared!';

            $this->files->delete($this->app->getCachedServicesPath());
            $this->files->delete($this->app->getCachedPackagesPath());
            $messages[] = 'Compiled services and packages files removed!';
            $messages[] = 'Caches cleared successfully!';

            return $this->plainTextResponse(
                "Optimize clear worked successfully.\n" . implode("\n", $messages)
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->plainTextResponse(
                "Optimize clear failed.\n" . $exception->getMessage(),
                500
            );
        }
    }

    private function plainTextResponse(string $message, int $status = 200): Response
    {
        return response($message, $status)
            ->header('Content-Type', 'text/plain');
    }

    private function clearCompiledViews(): void
    {
        $path = config('view.compiled');

        if (! $path) {
            throw new RuntimeException('View path not found.');
        }

        foreach ($this->files->glob("{$path}/*") ?: [] as $view) {
            $this->files->delete($view);
        }
    }

    private function flushFacades(): void
    {
        $storagePath = storage_path('framework/cache');

        if (! $this->files->exists($storagePath)) {
            return;
        }

        foreach ($this->files->files($storagePath) as $file) {
            if (preg_match('/facade-.*\.php$/', $file)) {
                $this->files->delete($file);
            }
        }
    }
}
