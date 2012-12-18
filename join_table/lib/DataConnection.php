<?php
//dengjing34@vip.qq.com
class DataConnection {

	private static $connection = null;
	const HOST = 'localhost';
	const USR = 'root';
	const PASSWD = 'root';
	const DBNAME = 'test';

	public static function getConnection() {
		if (self::$connection == null) {
			self::$connection = mysql_connect(self::HOST, self::USR, self::PASSWD) or die(mysql_error());
			mysql_select_db(self::DBNAME) or die(mysql_error());
			mysql_query('set names utf8') or die(mysql_error());
		}
		return self::$connection;
	}

}
?>
