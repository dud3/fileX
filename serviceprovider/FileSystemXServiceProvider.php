<?php

use Illuminate\Support\ServiceProvider;

class FileSystemXServiceProvider extends ServiceProvider {
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() 
    {
        $this->app->bind('FSX', function(){
            return new FileSystemX\FileSystemXGateway\FilesystemX; // Namespace\className
        });
    }
}
