<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Class to use MySQL DBMS
 * mysql handling class
 *
 * Does not use prepared statements, since mysql driver does not support them
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/db
 * @version 0.1
 */

class DBOracle extends DB
{
	/**
	 * prefix of a tablename (One or more XEs can be installed in a single DB)
	 * @var string
	 */
	var $prefix = 'xe_';
	var $comment_syntax = '/* %s */';

	/**
	 * Column type used in MySQL
	 *
	 * Becasue a common column type in schema/query xml is used for colum_type,
	 * it should be replaced properly for each DBMS
	 * @var array
	 */
	var $column_type = array(
		'bignumber' => 'number',
		'number' => 'number',
		'varchar' => 'nvarchar2',
		'char' => 'nchar',
		'text' => 'long',
		'bigtext' => 'lob',
		'date' => 'nvarchar2(14)',
		'float' => 'binary_float',
	);

	/**
	 * constructor
	 * @return void
	 */
	function DBOracle()
	{
		$this->_setDBInfo();
		$this->_connect();
	}
	
	/**
	 * create an instance of this class
	 * @return DBOracle return DBOracle object instance
	 */
	function create()
	{
		return new DBOracle;	
	}

	/**
	 * DB Connect
	 * this method is private
	 * @param array $connection connection's value is db_hostname, db_port, db_database, db_userid, db_password
	 * @return resource
	 */
	function __connect($connection)
	{
		// Ignore if no DB information exists
		if(strpos($connection["db_hostname"], ':') === false && $connection["db_port"])
		{
			$connection["db_hostname"] .= ':' . $connection["db_port"];
		}

		$result = oci_connect($connection["db_userid"], $connection["db_password"], $connection["db_hostname"]);
		if (!$result)
		{
			exit('XE cannot connect to DB.'); 
		}

		if (oci_error())
		{
			$error = oci_error();
			$this->setError($error['code'], $error['message']);
			return;
		}

		return $result;
	}

	/**
	 * DB disconnection
	 * this method is private
	 * @param resource $connection
	 * @return void
	 */
	function _close($connection)
	{
		$this->commit();
		oci_close($connection);
	}

	/**
	 * Handles quatation of the string variables from the query
	 * @todo See what to do about this
	 * @param string $string
	 * @return string
	 */
	function addQuotes($string)
	{
		if (version_compare(PHP_VERSION, "5.4.0", "<") && get_magic_quotes_gpc())
		{
			$string = stripslashes(str_replace("\\", "\\\\", $string));
		}

		return $string;
	}

	/**
	 * DB transaction start
	 * this method is private
	 * @return boolean
	 */
	function _begin($transactionLevel)
	{
		$connection = $this->_getConnection('master');
		
		if (!$transsactionLevel)
		{
			$this->_query("begin");			
		}
		else
		{
			$this->_query("SAVEPOINT SP" . $transactionLevel, $connection);
		}

		return true;
	}

	/**
	 * DB transaction rollback
	 * this method is private
	 * @return boolean
	 */
	function _rollback($transactionLevel)
	{
		$connection = $this->_getConnection('master');

		$point = $transactionLevel - 1;

		if ($point)
		{
			$this->_query("ROLLBACK TO SP" . $point, $connection);
		}
		else
		{
			oci_rollback($connection);
		}

		return true
	}

	/**
	 * DB transaction commit
	 * this method is private
	 * @return boolean
	 */
	function _commit()
	{
		$connection = $this->_getConnection('master');
		oci_commit($connection);
		return true;
	}
}

DBOracle::$isSupported = function_exists('oracle_connect');

/* End of file DBOracle.class.php */
/* Location: ./classes/db/DBOracle.class.php */
?>
