<?php

namespace app;

class card
{

	/** @var array<int, array> */
	private $card_cat = [];

	/** @var array<int, array> */
	private $card = [];

	public function __construct(protected db $db)
	{
		$this->load_cat();
		$this->load();
	}//end __construct()

	private function load_cat() : void
	{
		$sql    = 'SELECT id, name FROM card_cat';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$id = (int) $row['id'];

			$this->card_cat[$id] = ['name' => $row['name'], 'balance_0' => 0.0, 'balance_1' => 0.0, 'balance_2' => 0.0];
		}
	}//end load_cat()

	private function load() : void
	{
		$sql    = 'SELECT card_id, cat_id, name, type, balance FROM card';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$id      = (int) $row['card_id'];
			$cat_id  = (int) $row['cat_id'];
			$balance = (float) $row['balance'];
			$type    = (int) $row['type'];

			$this->card[$id] = ['cat' => $cat_id, 'name' => $row['name'], 'type' => $type, 'balance' => $balance];
			assert(is_float($this->card_cat[$cat_id]['balance_'.$type]));
			$this->card_cat[$cat_id]['balance_'.$type] += $balance;
		}
	}//end load()

	/**
	 * @return array<int, array>
	 */
	public function get_cat()
	{
		return $this->card_cat;
	}//end get_cat()

	/**
	 * @return array<int, array>
	 */
	public function get_card()
	{
		return $this->card;
	}//end get_card()

	public function change_balance(int $card_id, float $balance) : void
	{
		$sql = 'UPDATE card SET balance = balance+'.$balance.' WHERE card_id='.$card_id;
		$this->db->sql_query($sql);
	}//end change_balance()

}//end class
