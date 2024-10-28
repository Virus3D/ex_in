<?php

namespace app;

class receipt
{

	public function __construct(protected db $db)
	{
	}//end __construct()

	public function add(array $data) : void
	{
		$data = [
			'card_id' => $data['card'],
			'date'    => $data['date'],
			'balance' => $data['balance'],
			'comm'    => $data['comm']
		];
		$sql = 'INSERT IGNORE receipt '.$this->db->build_ins($data);
		$this->db->sql_query($sql);
	}//end add()

	/**
	 * @param  string $date [description]
	 * @return array<array>
	 */
	public function get(string $date) : array
	{
		$list   = [];
		$sql    = 'SELECT * FROM receipt WHERE date LIKE "'.$date.'%" ORDER BY date';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$list[] = $row;
		}

		return $list;
	}//end get()

	public function get_by_id(int $id) : array
	{
		$list   = [];
		$sql    = 'SELECT * FROM receipt WHERE receipt_id = '.$id.' LIMIT 1';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$list = $row;
		}

		return $list;
	}//end get_by_id()

	public function del(int $id) : void
	{
		$sql = 'DELETE FROM receipt WHERE receipt_id = '.$id;
		$this->db->sql_query($sql);
	}//end del()

	public function get_balance(string $date) : array
	{
		$balance = [];
		$sql     = 'SELECT card_id, SUM(balance) as balance_sum FROM receipt WHERE date LIKE "'.$date.'%" GROUP BY card_id';
		$result  = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$card_id = (int) $row['card_id'];

			$balance[$card_id] = (float) $row['balance_sum'];
		}

		return $balance;
	}//end get_balance()

	public function get_datalist() : string
	{
		$datalist = '';
		$sql      = 'SELECT DISTINCT comm FROM receipt ORDER BY comm';
		$result   = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			assert(is_string($row['comm']));
			$datalist .= '<option value="'.$row['comm'].'">';
		}

		return $datalist;
	}//end get_datalist()

}//end class
