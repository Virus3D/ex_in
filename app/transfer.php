<?php

namespace app;

class transfer
{

	public function __construct(protected db $db)
	{
	}//end __construct()

	public function add(array $data) : void
	{
		$data = [
			'card_id1' => $data['card1'],
			'card_id2' => $data['card2'],
			'date'     => $data['date'],
			'balance'  => $data['balance'],
		];
		$sql = 'INSERT IGNORE transfer '.$this->db->build_ins($data);
		$this->db->sql_query($sql);
	}//end add()

	/**
	 * @param  string $date [description]
	 * @return array<array>
	 */
	public function get(string $date) : array
	{
		$list   = [];
		$sql    = 'SELECT * FROM transfer WHERE date LIKE "'.$date.'%" ORDER BY date';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$list[] = $row;
		}

		return $list;
	}//end get()

	public function get_balance(string $date) : array
	{
		$balance = [];
		$sql     = 'SELECT card_id1, card_id2, balance FROM transfer WHERE date LIKE "'.$date.'%"';
		$result  = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$card_id1 = (int) $row['card_id1'];
			$card_id2 = (int) $row['card_id2'];

			if (! isset($balance[$card_id1]['spend']))
			{
				$balance[$card_id1]['spend'] = 0;
			}

			if (! isset($balance[$card_id2]['receipt']))
			{
				$balance[$card_id2]['receipt'] = 0;
			}

			$balance[$card_id1]['spend']   += (float) $row['balance'];
			$balance[$card_id2]['receipt'] += (float) $row['balance'];
		}

		return $balance;
	}//end get_balance()

}//end class
