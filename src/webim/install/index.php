<?php
/*
 * This file is part of Web Instant Messenger project.
 *
 * Copyright (c) 2005-2008 Internet Services Ltd.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *    Evgeny Gryaznov - initial API and implementation
 */

require('../libs/common.php');
require('dbinfo.php');

$page = array(
	'version' => $version,
	'localeLinks' => get_locale_links("$webimroot/install/index.php")
);


$page['done'] = array();
$page['nextstep'] = false;
$page['nextnotice'] = false;
$errors = array();

function check_connection() {
	global $mysqlhost,$mysqllogin,$mysqlpass, $page, $errors;
	$link = @mysql_connect($mysqlhost,$mysqllogin,$mysqlpass);
	if ($link) {
		$result = mysql_query("SELECT VERSION() as c", $link);
		if( $result && $ver = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$page['done'][] = getstring2("install.1.connected", array($ver['c']));
			mysql_free_result($result);
		} else {
			$errors[] = "Version of your SQL server is unknown. Please check. Error: ".mysql_error();
			mysql_close($link);
			return null;
		}
		return $link;
	} else {
		$errors[] = getstring2("install.connection.error", array(mysql_error()));
		return null;
	}
}

function check_database($link) {
	global $mysqldb, $force_charset_in_connection, $dbencoding, $page;
	if(mysql_select_db($mysqldb,$link)) {
		$page['done'][] = getstring2("install.2.db_exists", array($mysqldb));
		if( $force_charset_in_connection ) {
			mysql_query("SET character set $dbencoding", $link);
		}
		return true;
	} else {
		$page['nextstep'] = getstring2("install.2.create", array($mysqldb));
		$page['nextnotice'] = getstring("install.2.notice");
		$page['nextstepurl'] = "dbperform.php?act=createdb";
	}
	return false;
}

function check_tables($link) {
	global $dbtables, $page;
	$curr_tables = get_tables($link);
	if( $curr_tables !== false) {
		$tocreate = array_diff(array_keys($dbtables), $curr_tables);
		if( count($tocreate) == 0 ) {
			$page['done'][] = getstring("install.3.tables_exist");
			return true;
		} else {
			$page['nextstep'] = getstring("install.3.create");
			$page['nextstepurl'] = "dbperform.php?act=createtables";
		}
	}
	return false;
}

function check_columns($link) {
	global $dbtables, $dbtables_can_update, $errors, $page;

	$need_to_create_columns = false;
	foreach( $dbtables as $id => $columns) {
		$curr_columns = get_columns($id, $link);
		if( $curr_columns === false ) {
			return false;
		}
		$tocreate = array_diff(array_keys($columns), $curr_columns);
		if( count($tocreate) != 0 ) {
			$cannot_update = array_diff($tocreate, $dbtables_can_update[$id]);
			if( count($cannot_update) != 0) {
				$errors[] = "Key columns are absent in table `$id'. Unable to continue installation.";
				$page['nextstep'] = getstring("install.kill_tables");
				$page['nextstepurl'] = "dbperform.php?act=droptables";
				$page['nextnotice'] = getstring("install.kill_tables.notice");
				return false;
			}
			$need_to_create_columns = true;
		}
	}

	if( $need_to_create_columns ) {
		$page['nextstep'] = getstring("install.4.create");
		$page['nextstepurl'] = "dbperform.php?act=addcolumns";
		$page['nextnotice'] = getstring("install.4.notice");
		return false;
	}

	$page['done'][] = getstring("install.4.done");
	return true;
}

function check_status() {
	global $page, $webimroot;
	$link = check_connection();
	if(!$link) {
		return;
	}

	if( !check_database($link)) {
		mysql_close($link);
		return;
	}

	if( !check_tables($link)) {
		mysql_close($link);
		return;
	}

	if( !check_columns($link)) {
		mysql_close($link);
		return;
	}

	$page['done'][] = getstring("installed.message");

	$page['nextstep'] = getstring("installed.login_link");
	$page['nextnotice'] = getstring("installed.notice");
	$page['nextstepurl'] = "$webimroot/";

	mysql_close($link);
}

check_status();

start_html_output();
require('view_index.php');
?>