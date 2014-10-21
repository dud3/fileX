<?php

use Illuminate\Support\Facades\Facade;

class FilesystemXFacade extends Facade {
 
    protected static function getFacadeAccessor() { return 'FSX'; } // use FSX::method()

}