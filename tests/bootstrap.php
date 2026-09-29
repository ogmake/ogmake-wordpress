<?php
require_once dirname( __DIR__ ) . '/vendor/autoload.php';
define( 'ABSPATH', __DIR__ . '/' );
define( 'OGMAKE_DIR', dirname( __DIR__ ) . '/' );
define( 'DAY_IN_SECONDS', 86400 );
foreach ( array( 'signing', 'settings', 'url', 'frontend', 'metabox' ) as $f ) {
	require_once OGMAKE_DIR . "includes/class-ogmake-$f.php";
}
