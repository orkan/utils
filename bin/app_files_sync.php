<?php
/*
 * This file is part of the orkan/utils package.
 * Copyright (c) 2020 Orkan <orkans+utils@gmail.com>
 */
use Orkan\Factory;
use Orkan\AppFilesSync;

require $GLOBALS['_composer_autoload_path'] ?? $_ENV['COMPOSER_AUTOLOAD'];

$Factory = new Factory();
$App = new AppFilesSync( $Factory );
$Utils = $Factory->Utils();
$Logger = $Factory->Logger();

$Logger->info( '' );
$Logger->info( "====================================================================================================" );
$Logger->info( $App->getWelcome() );
$Logger->info( sprintf( 'Sync: "%s"', $Factory->get( 'sync_dir_out' ) ) );
DEBUG && $Logger->info( 'Config: ' . $Factory->get( 'cfg_user' ) );
$Logger->info( "====================================================================================================" );
$App->run();
$Logger->info( "====================================================================================================" );
$Logger->info( $App->getWelcome( 'APP: {title} v{version}' ) );
$Logger->info( 'PHP: ' . implode( '. ', $Utils->phpSummary() ) );
$Logger->info( 'Bye!' );

$Utils->writeln( implode( "\n", $Logger->getHistoryLogs() ) );
