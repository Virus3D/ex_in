<?php

namespace app;

class api
{

	/** @var db */
	protected $db;

	/** @var template */
	protected $template;

	/** @var card */
	protected $card;

	/** @var receipt */
	protected $receipt;

	/** @var spend */
	protected $spend;

	/** @var transfer */
	protected $transfer;

	/** @var validate */
	protected $validate;

	/** @var string */
	protected $date;

	/** @var string */
	protected $year;

	/** @var string */
	protected $month;

	/** @var array */
	protected $cats = [];

	/** @var array */
	protected $cards = [];

	public function __construct()
	{
		$this->db = new db('localhost', 'ex_in', 'admin', 'adminadmin');
		$this->db->connect();

		$this->template = new template('template');
		$this->card     = new card($this->db);
		$this->validate = new validate();
		/** @var string */
		$this->year = request_var('year', date('Y'));
		/** @var string */
		$this->month    = request_var('month', date('m'));
		$this->date     = $this->year.'.'.$this->month.'.';
		$this->receipt  = new receipt($this->db);
		$this->spend    = new spend($this->db);
		$this->transfer = new transfer($this->db);
	}//end __construct()

	public function build() : void
	{
		$card_list = $this->card_load();
		$this->card_select($card_list);

		$this->template->assign_vars([
			'YEAR'             => $this->year,
			'MONTH'            => $this->get_month(),
			'DATALIST_RECEIPT' => $this->receipt->get_datalist(),
			'DATALIST_SPEND'   => $this->spend->get_datalist(),
		]);

		echo $this->template->render('index.html');
	}//end build()

	private function get_month() : string
	{
		/** @var array<string,string> */
		$months = [
			'01' => 'Январь',
			'02' => 'Февраль',
			'03' => 'Март',
			'04' => 'Апрель',
			'05' => 'Май',
			'06' => 'Июнь',
			'07' => 'Июль',
			'08' => 'Август',
			'09' => 'Сентябрь',
			'10' => 'Октябрь',
			'11' => 'Ноябрь',
			'12' => 'Декабрь',
		];
		$option = '';
		foreach ($months as $id => $month)
		{
			$option .= '<option value="'.$id.'"';
			if ($this->month == $id)
			{
				$option .= ' selected';
			}

			$option .= '>'.$month.'</option>';
		}

		return $option;
	}//end get_month()

	private function card_load() : array
	{
		$receipt_balance  = $this->receipt->get_balance($this->date);
		$spend_balance    = $this->spend->get_balance($this->date);
		$transfer_balance = $this->transfer->get_balance($this->date);

		$this->cats  = $this->card->get_cat();
		$this->cards = $this->card->get_card();

		$tmpl = [];
		foreach ($this->cats as $id => $cat)
		{
			$tmpl[$id] = array_change_key_case($cat, CASE_UPPER);

			$tmpl[$id]['card'] = [];

			$tmpl[$id]['RECEIPT'] = 0;
			$tmpl[$id]['SPEND']   = 0;
		}

		foreach ($this->cards as $id => $card)
		{
			/** @var int */
			$cat = $card['cat'];

			$tmpl[$cat]['card'][$id]            = array_change_key_case($card, CASE_UPPER);
			$tmpl[$cat]['card'][$id]['RECEIPT'] = ($receipt_balance[$id] ?? 0) + ($transfer_balance[$id]['receipt'] ?? 0);
			$tmpl[$cat]['card'][$id]['SPEND']   = ($spend_balance[$id] ?? 0) + ($transfer_balance[$id]['spend'] ?? 0);
			if ($card['type'] == 0)
			{
				$tmpl[$cat]['RECEIPT'] += ($receipt_balance[$id] ?? 0);
				$tmpl[$cat]['SPEND']   += ($spend_balance[$id] ?? 0);
			}
		}

		return $tmpl;
	}//end card_load()

	public function card_tmpl() : void
	{
		$tmpl = $this->card_load();
		$this->template->assign_vars(['card' => $tmpl]);
		echo $this->template->render('card_block.html');
	}//end card_tmpl()

	public function receipt_tmpl() : void
	{
		$this->card_load();
		/** @var int */
		$card = request_var('card', 0);
		$this->load_receipt($card);
		echo $this->template->render('receipt.html');
	}//end receipt_tmpl()

	public function spend_tmpl() : void
	{
		$this->card_load();
		/** @var int */
		$card = request_var('card', 0);
		$this->load_spend($card);
		echo $this->template->render('spend.html');
	}//end spend_tmpl()

	public function transfer_tmpl() : void
	{
		$this->card_load();
		/** @var int */
		$card1 = request_var('card1', 0);
		$card2 = request_var('card2', 0);
		$this->load_transfer($card1, $card2);
		echo $this->template->render('transfer.html');
	}//end transfer_tmpl()

	private function card_select(array $card_list) : void
	{
		$opt = '';
		foreach ($card_list as $item)
		{
			assert(is_array($item));
			assert(is_string($item['NAME']));
			assert(is_array($item['card']));
			$opt .= '<optgroup label="'.$item['NAME'].'">';
			foreach ($item['card'] as $id => $card)
			{
				assert(is_array($card));
				assert(is_string($card['NAME']));
				$opt .= '<option value="'.$id.'">'.$card['NAME'].'</option>';
			}

			$opt .= '</optgroup>';
		}

		$this->template->assign_vars(['CARD_OPT' => $opt]);
	}//end card_select()

	private function load_receipt(int $card) : void
	{
		$list = $this->receipt->get($this->date);
		$tmpl = [];
		$sum  = 0;
		foreach ($list as $item)
		{
			$card_id = (int) $item['card_id'];
			if ($card && $card != $card_id)
			{
				continue;
			}

			$cat_id = (int) ($this->cards[$card_id]['cat'] ?? 0);
			$tmpl[] = [
				'CARD'     => $this->cards[$card_id]['name'] ?? '',
				'CARD_CAT' => $this->cats[$cat_id]['name'] ?? '',
				'DATE'     => $item['date'],
				'BALANCE'  => $item['balance'],
				'COMM'     => $item['comm'],
				'U_DEL'    => '/?task=del_receipt&id='.$item['receipt_id'],
			];
			$sum += (float) $item['balance'];
		}

		$this->template->assign_vars([
			'RECEIPT_SUM' => $sum,
			'receipt'     => $tmpl
		]);
	}//end load_receipt()

	private function load_spend(int $card) : void
	{
		$list = $this->spend->get($this->date);
		$tmpl = [];
		$sum  = 0;
		foreach ($list as $item)
		{
			$card_id = (int) $item['card_id'];
			if ($card && $card != $card_id)
			{
				continue;
			}

			$cat_id = (int) ($this->cards[$card_id]['cat'] ?? 0);
			$tmpl[] = [
				'CARD'     => $this->cards[$card_id]['name'] ?? '',
				'CARD_CAT' => $this->cats[$cat_id]['name'] ?? '',
				'DATE'     => $item['date'],
				'BALANCE'  => $item['balance'],
				'FLAG'     => ($item['balance'] == $item['balance_sum']) ? 2 : ((float) $item['balance_sum'] ? 1 : 0),
				'COMM'     => $item['comm'],
				'CHEQUE'   => '/?task=cheque&spend_id='.$item['spend_id']
			];
			$sum += (float) $item['balance'];
		}

		$this->template->assign_vars([
			'SPEND_SUM' => $sum,
			'spend'     => $tmpl
		]);
	}//end load_spend()

	private function load_transfer(int $card1, int $card2) : void
	{
		$list = $this->transfer->get($this->date);
		$tmpl = [];
		foreach ($list as $item)
		{
			$card1_id = (int) $item['card_id1'];
			if ($card1 && $card1 != $card1_id)
			{
				continue;
			}

			$card2_id = (int) $item['card_id2'];
			if ($card2 && $card2 != $card2_id)
			{
				continue;
			}

			$cat1_id = (int) ($this->cards[$card1_id]['cat'] ?? 0);
			$cat2_id = (int) ($this->cards[$card2_id]['cat'] ?? 0);
			$tmpl[]  = [
				'CARD1'     => $this->cards[$card1_id]['name'] ?? '',
				'CARD1_CAT' => $this->cats[$cat1_id]['name'] ?? '',
				'CARD2'     => $this->cards[$card2_id]['name'] ?? '',
				'CARD2_CAT' => $this->cats[$cat2_id]['name'] ?? '',
				'DATE'      => $item['date'],
				'BALANCE'   => $item['balance'],
			];
		}//end foreach

		$this->template->assign_vars([
			'transfer' => $tmpl
		]);
	}//end load_transfer()

	public function receipt() : void
	{
		$date = date('d.m.Y');
		$data = [
			'card'    => request_var('card', 0),
			'date'    => request_var('date', $date),
			'balance' => request_var('balance', ''),
			'comm'    => request_var('comm', '', true),
		];
		$this->validate->valid($data);
		$this->receipt->add($data);

		assert(is_int($data['card']));
		assert(is_float($data['balance']));
		$this->card->change_balance($data['card'], $data['balance']);
	}//end receipt()

	public function spend() : void
	{
		$date = date('d.m.Y');
		$data = [
			'card'    => request_var('card', 0),
			'date'    => request_var('date', $date),
			'balance' => request_var('balance', ''),
			'comm'    => request_var('comm', '', true),
		];
		$this->validate->valid($data);
		$this->spend->add($data);

		assert(is_int($data['card']));
		assert(is_float($data['balance']));
		$this->card->change_balance($data['card'], -$data['balance']);
	}//end spend()

	public function transfer() : void
	{
		$date = date('d.m.Y');
		$data = [
			'card1'   => request_var('card1', 0),
			'card2'   => request_var('card2', 0),
			'date'    => request_var('date', $date),
			'balance' => request_var('balance', ''),
		];
		$this->validate->valid($data);
		$this->transfer->add($data);

		assert(is_int($data['card1']));
		assert(is_int($data['card2']));
		assert(is_float($data['balance']));
		$this->card->change_balance($data['card1'], -$data['balance']);
		$this->card->change_balance($data['card2'], $data['balance']);
	}//end transfer()

	public function del_receipt() : void
	{
		/** @var int */
		$id   = request_var('id', 0);
		$data = $this->receipt->get_by_id($id);
		$this->validate->valid($data);
		$this->receipt->del($id);

		assert(is_int($data['card_id']));
		assert(is_float($data['balance']));
		$this->card->change_balance($data['card_id'], -$data['balance']);
	}//end del_receipt()

	public function cheque() : void
	{
		/** @var int */
		$spend_id    = request_var('spend_id', 0);
		$spend       = $this->spend->get_one($spend_id);
		$item        = new item($this->db);
		$cheque      = new cheque($this->db);
		$cheque_list = $cheque->list($spend_id);
		$item_ids    = array_column($cheque_list, 'item_id');
		$item_list   = $item->list($item_ids);
		$list        = [];
		$sum         = 0;
		foreach ($cheque_list as $ch_item)
		{
			$list[] = [
				'NAME' => $item_list[$ch_item['item_id']],
				'COST' => $ch_item['cost']
			];
			$sum += (float) $ch_item['cost'];
		}

		$this->template->assign_vars([
			'SPEND_ID'      => $spend['spend_id'],
			'SPEND_NAME'    => $spend['comm'],
			'SPEND_BALANCE' => $spend['balance'],
			'SPEND_SUM'     => $sum,
			'SPEND_SUM_DEF' => $spend['balance'] - $sum,
			'DATALIST_ITEM' => $item->get_datalist(),
			'list'          => $list,
		]);

		if ($spend['balance_sum'] != $sum)
		{
			$this->spend->update_balance_sum($spend_id, $sum);
		}

		echo $this->template->render('cheque.html');
	}//end cheque()

	public function cheque_add() : void
	{
		/** @var int */
		$spend_id = request_var('spend_id', 0);
		/** @var string */
		$cost = request_var('cost', '');
		/** @var string */
		$item_name = request_var('item', '', true);

		$item   = new item($this->db);
		$cheque = new cheque($this->db);

		$item_id = $item->get_id($item_name);

		$data = [
			'spend_id' => $spend_id,
			'item_id'  => $item_id,
			'cost'     => $cost,
		];
		$this->validate->valid($data);
		$cheque->add($data);

		$this->cheque();
	}//end cheque_add()

}//end class
