<?php

namespace app;

class spend
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
		$sql = 'INSERT IGNORE spend '.$this->db->build_ins($data);
		$this->db->sql_query($sql);
	}//end add()

	/**
	 * @param  string $date [description]
	 * @return array<array>
	 */
	public function get(string $date) : array
	{
		$list   = [];
		$sql    = 'SELECT * FROM spend WHERE date LIKE "'.$date.'%" ORDER BY date';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$list[] = $row;
		}

		return $list;
	}//end get()

	public function get_one(int $spend_id) : array
	{
		$sql    = 'SELECT * FROM spend WHERE spend_id = '.$spend_id;
		$result = $this->db->sql_query($sql);
		$row    = $this->db->sql_fetchrow($result);

		return $row ?: [];
	}//end get_one()

	public function get_balance(string $date) : array
	{
		$balance = [];
		$sql     = 'SELECT card_id, SUM(balance) as balance_sum FROM spend WHERE date LIKE "'.$date.'%" GROUP BY card_id';
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
		$sql      = 'SELECT DISTINCT comm FROM spend ORDER BY comm';
		/** @var mixed */
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			assert(is_string($row['comm']));
			$datalist .= '<option value="'.$row['comm'].'">';
		}

		return $datalist;
	}//end get_datalist()

	public function update_balance_sum(int $spend_id, float $sum) : void
	{
		$sql = 'UPDATE spend SET balance_sum = '.$sum.' WHERE spend_id='.$spend_id;
		$this->db->sql_query($sql);
	}//end update_balance_sum()

}//end class
