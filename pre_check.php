<?php
//
// Copyright (C) 1999-2004 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE included in
// the packaging of this file.
//
// Licencees holding a valid "eZ publish professional licence" version 2
// may use this file in accordance with the "eZ publish professional licence"
// version 2 Agreement provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" version 2 is available at
// http://ez.no/ez_publish/licences/professional/ and in the file
// PROFESSIONAL_LICENCE included in the packaging of this file.
// For pricing of this licence please contact us via e-mail to licence@ez.no.
// Further contact information is available at http://ez.no/company/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

include_once( "lib/ezutils/classes/ezhttptool.php" );

/*!
 Checks if the installation is valid and returns a module redirect if required.
 If CheckValidity in SiteAccessSettings is false then no check is done.
*/

function eZCheckValidity( &$siteBasics, &$uri )
{
//     eZDebug::writeDebug( "Checking validity" );
    $ini =& eZINI::instance();
    $checkValidity = ( $ini->variable( "SiteAccessSettings", "CheckValidity" ) == "true" );
    $check = null;
    if ( $checkValidity )
    {
//         eZDebug::writeDebug( "Setup required" );
        $check = array( "module" => "setup",
                        'function' => 'init' );
        // Turn off some features that won't bee needed yet
//        $siteBasics['policy-check-required'] = false;
        $siteBasics['policy-check-omit-list'][] = 'setup';
        $siteBasics['url-translator-allowed'] = false;
        $siteBasics['show-page-layout'] = $ini->variable( 'SetupSettings', 'PageLayout' );
        $siteBasics['validity-check-required'] = true;
        $siteBasics['user-object-required'] = false;
        $siteBasics['session-required'] = false;
        $siteBasics['db-required'] = false;
        $siteBasics['no-cache-adviced'] = true;
        $siteBasics['site-design-override'] = $ini->variable( 'SetupSettings', 'OverrideSiteDesign' );
        $access = array( 'name' => 'setup',
                         'type' => EZ_ACCESS_TYPE_URI );
        $access = changeAccess( $access );
        $GLOBALS['eZCurrentAccess'] = $access;
    }
    return $check;
}

/*!
 \return an array with items to run a check on, each items
 is an associative array. The item must contain:
 - function - name of the function to run
*/
function eZCheckList()
{
    $checks = array();
    $checks["validity"] = array( "function" => "eZCheckValidity" );
    $checks["user"] = array( "function" => "eZCheckUser" );
    return $checks;
}

/*!
 Check if user login is required. If so, use login handler to redirect user.
*/
function eZCheckUser( &$siteBasics, &$uri )
{
    include_once( 'kernel/classes/datatypes/ezuser/ezuserloginhandler.php' );
    if ( !$siteBasics['user-object-required'] )
    {
        return null;
    }

    $http =& eZHTTPTool::instance();
    $ini =& eZINI::instance();
    $requireUserLogin = ( $ini->variable( "SiteAccessSettings", "RequireUserLogin" ) == "true" );
    $forceLogin =& $http->hasSessionVariable( EZ_LOGIN_HANDLER_FORCE_LOGIN );
    if ( !$requireUserLogin &&
         !$forceLogin )
    {
        return null;
    }

    return eZUserLoginHandler::checkUser( $siteBasics, $uri );
}

/*!
 \return an array with check items in the order they should be checked.
*/
function eZCheckOrder()
{
    $checkOrder = array( 'validity', 'user' );
    return $checkOrder;
}

/*!
 Does pre checks and returns a structure with redirection information,
 returns null if nothing should be done.
*/
function eZHandlePreChecks( &$siteBasics, &$uri )
{
    $checks = eZCheckList();
    precheckAllowed( $checks );
    $checkOrder = eZCheckOrder();
    foreach( $checkOrder as $checkItem )
    {
        if ( !isset( $checks[$checkItem] ) )
            continue;
        $check = $checks[$checkItem];
        if ( !isset( $check["allow"] ) or $check["allow"] )
        {
            $func = $check["function"];
            $check = $func( $siteBasics, $uri );
            if ( $check !== null )
                return $check;
        }
    }
    return null;
}


?>
