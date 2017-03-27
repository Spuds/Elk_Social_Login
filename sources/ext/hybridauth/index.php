<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

// ------------------------------------------------------------------------
// ElkArte uses its own sessions, we need to invoke them
// ------------------------------------------------------------------------
require_once( "../../../SSI.php" );

// ------------------------------------------------------------------------
//	HybridAuth End Point
// ------------------------------------------------------------------------

require_once( "Hybrid/Auth.php" );
require_once( "Hybrid/Endpoint.php" );

Hybrid_Endpoint::process();
