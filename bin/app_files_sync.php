<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
use Orkan\Factory;
use Orkan\AppFilesSync;

// require __DIR__ . '/../tools/composer/autoload.php';
require $GLOBALS['_composer_autoload_path'];

$Factory = new Factory();
$App = new AppFilesSync( $Factory );
$Utils = $Factory->Utils();
$Logger = $Factory->Logger();

$Logger->info( "================================================================================" );
$Logger->info( $App->getVersion() );
$Logger->debug( 'Config: ' . $Factory->get( 'cfg_user' ) );
$Logger->info( "================================================================================" );
$App->run();
$Logger->info( "================================================================================" );
$Logger->info( implode( '. ', $Utils->phpSummary() ) );
$Logger->info( 'Bye!' );

$Utils->writeln( implode( "\n", $Logger->getHistoryLogs() ) );
