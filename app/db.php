<?php

namespace app;

use mysqli;
use mysqli_result;
use mysqli_sql_exception;

class db
{

	/** @var string */
	protected $srv;

	/** @var string */
	protected $usr;

	/** @var string */
	protected $pwd;

	/** @var string */
	protected $db;

	/** @var mysqli|false */
	protected $db_id = false;

	/** @var mysqli_result|bool */
	protected $query_result = false;

	/** @var bool */
	protected $con = false;

	/** @var int */
	public $transaction = 0;

	public function __construct(string $srv, string $db, string $login, string $pwd)
	{
		$this->srv = $srv;
		$this->usr = $login;
		$this->pwd = $pwd;
		$this->db  = $db;
	}//end __construct()

	public function connect() : mysqli|false
	{
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		if (! function_exists('mysqli_connect'))
		{
			$this->connect_error = 'mysqli_connect function does not exist, is mysqli extension installed?';

			return $this->sql_error('');
		}

		$this->db_id = mysqli_init();

		if (! @mysqli_real_connect($this->db_id, $this->srv, $this->usr, $this->pwd, $this->db, null, null, MYSQLI_CLIENT_FOUND_ROWS))
		{
			$this->db_id = false;
		}

		if (! $this->db_id || $this->db == '')
		{
			return $this->sql_error('');
		}

		@mysqli_query($this->db_id, "SET NAMES 'utf8'");

		// enforce strict mode on databases that support it
		if (version_compare($this->sql_server_info(true), '5.0.2', '>='))
		{
			$modes  = [];
			$result = @mysqli_query($this->db_id, 'SELECT @@session.sql_mode AS sql_mode');
			if ($result !== null)
			{
				$row   = @mysqli_fetch_assoc($result);
				$modes = array_map('trim', explode(',', $row['sql_mode']));
			}

			@mysqli_free_result($result);

			// TRADITIONAL includes STRICT_ALL_TABLES and STRICT_TRANS_TABLES
			if (! in_array('TRADITIONAL', $modes))
			{
				if (! in_array('STRICT_ALL_TABLES', $modes))
				{
					$modes[] = 'STRICT_ALL_TABLES';
				}

				if (! in_array('STRICT_TRANS_TABLES', $modes))
				{
					$modes[] = 'STRICT_TRANS_TABLES';
				}
			}

			$mode = implode(',', $modes);
			@mysqli_query($this->db_id, "SET SESSION sql_mode='{$mode}'");
		}//end if

		return $this->db_id;
	}//end connect()

	public function sql_error(string $sql = '') : array
	{

		// Set var to retrieve errored status
		$this->sql_error_triggered = true;
		$this->sql_error_sql       = $sql;

		$this->sql_error_returned = $this->_sql_error();

		$message = 'SQL ERROR [ mysqli ]<br /><br />'.$this->sql_error_returned['message'].' ['.$this->sql_error_returned['code'].']';

		// Show complete SQL error and path to administrators only
		// Additionally show complete error on installation or if extended debug mode is enabled
		// The DEBUG constant is for development only!
		$message .= ($sql) ? '<br /><br />SQL<br /><br />'.htmlspecialchars($sql) : '';

		if ($this->transaction)
		{
			$this->sql_transaction('rollback');
		}

		if (strlen($message) > 1024)
		{
			// We need to define $msg_long_text here to circumvent text stripping.
			global $msg_long_text;
			$msg_long_text = $message;

			trigger_error(false, E_USER_ERROR);
		}

		trigger_error($message, E_USER_ERROR);

		if ($this->transaction)
		{
			$this->sql_transaction('rollback');
		}

		return $this->sql_error_returned;
	}

	public function _sql_error() : array
	{
		if ($this->db_id)
		{
			return [
				'message' => @mysqli_error($this->db_id),
				'code'    => @mysqli_errno($this->db_id)
			];
		}

		if (function_exists('mysqli_connect_error'))
		{
			return [
				'message' => @mysqli_connect_error(),
				'code'    => @mysqli_connect_errno(),
			];
		}

		return [
			'message' => $this->connect_error,
			'code'    => '',
		];
	}//end _sql_error()

	public function sql_server_info($raw = false)
	{
		$result = @mysqli_query($this->db_id, 'SELECT VERSION() AS version');
		if ($result !== null)
		{
			$row = @mysqli_fetch_assoc($result);

			$this->sql_server_version = $row['version'];
		}
		@mysqli_free_result($result);

		return ($raw) ? $this->sql_server_version : 'MySQL(i) '.$this->sql_server_version;
	}

	public function sql_query(string $query = '') : mysqli_result|bool
	{
		if ($query == '' || $this->db_id == false)
		{
			return false;
		}

		try
		{
			$this->query_result = mysqli_query($this->db_id, $query);
		}
		catch (mysqli_sql_exception $e)
		{
			// $this->sql_error($e->__toString());
			$this->sql_error($query);
		}

		return $this->query_result;
	}//end sql_query()

	public function sql_fetchrow(mysqli_result|bool $query_id = false) : array|false
	{
		if ($query_id === false)
		{
			$query_id = $this->query_result;
		}

		if ($query_id !== false)
		{
			$result = mysqli_fetch_assoc($query_id);

			return $result !== null ? $result : false;
		}

		return false;
	}//end sql_fetchrow()

	public function sql_affectedrows()
	{
		return ($this->db_id) ? mysqli_affected_rows($this->db_id) : false;
	}

	public function sql_nextid() : int
	{
		return ($this->db_id) ? (int) mysqli_insert_id($this->db_id) : 0;
	}//end sql_nextid()

	public function build_ins(array $data) : string
	{
		$str_n = $str_v = [];
		foreach ($data as $name => $value)
		{
			$str_n[] = '`'.$name.'`';
			$str_v[] = '"'.(string) $value.'"';
		}

		$str = '('.implode(', ', $str_n).') VALUES ('.implode(', ', $str_v).')';

		return $str;
	}//end build_ins()

}//end class
