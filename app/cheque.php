<?php

namespace app;

class cheque
{

	public function __construct(protected db $db)
	{
	}//end __construct()

	public function add(array $data) : void
	{
		$sql = 'INSERT IGNORE cheque '.$this->db->build_ins($data);
		$this->db->sql_query($sql);
	}//end add()

	/**
	 * @param  int $spend_id [description]
	 * @return array<array>
	 */
	public function list(int $spend_id) : array
	{
		$list   = [];
		$sql    = 'SELECT * FROM cheque WHERE spend_id = '.$spend_id.' ORDER BY cheque_id';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$list[] = $row;
		}

		return $list;
	}//end list()

}//end class
