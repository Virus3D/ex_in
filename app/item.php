<?php

namespace app;

class item
{

	public function __construct(protected db $db)
	{
	}//end __construct()

	public function add(string $name) : int
	{
		$data = [
			'item_name' => $name,
		];
		$sql = 'INSERT IGNORE item '.$this->db->build_ins($data);
		$this->db->sql_query($sql);

		return $this->db->sql_nextid();
	}//end add()

	public function get_id(string $name) : int
	{
		$item_id = $this->get_id_name($name);
		if ($item_id)
		{
			return $item_id;
		}

		return $this->add($name);
	}//end get_id()

	public function get_id_name(string $name) : int
	{
		$sql    = 'SELECT * FROM item WHERE item_name ="'.$name.'"';
		$result = $this->db->sql_query($sql);
		$row    = $this->db->sql_fetchrow($result);
		if ($row)
		{
			return (int) $row['item_id'];
		}

		return 0;
	}//end get_id_name()

	/**
	 * @param  string $date [description]
	 * @return array<int, string>
	 */
	public function list(array $item_ids) : array
	{
		if (empty($item_ids))
		{
			return [];
		}

		$list   = [];
		$sql    = 'SELECT * FROM item WHERE item_id IN ('.implode(',', $item_ids).') ORDER BY item_name';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			assert(is_string($row['item_name']));
			$list[(int) $row['item_id']] = $row['item_name'];
		}

		return $list;
	}//end list()

	public function get_datalist() : string
	{
		$datalist = '';
		$sql      = 'SELECT item_name FROM item ORDER BY item_name';
		$result   = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			assert(is_string($row['item_name']));
			$datalist .= '<option value="'.$row['item_name'].'">';
		}

		return $datalist;
	}//end get_datalist()

}//end class
