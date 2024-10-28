<?php

namespace app;

class validate
{

	public function valid(array &$data) : void
	{
		foreach ($data as $name => $value)
		{
			if (method_exists($this, $name))
			{
				$data[$name] = $this->$name($value);
			}
		}
	}//end valid()

	private function card(mixed $value) : int
	{
		return (int) $value;
	}//end card()

	private function card_id(mixed $value) : int
	{
		return (int) $value;
	}//end card()

	private function balance(mixed $value) : float
	{
		if (is_float($value))
		{
			return $value;
		}

		$value = str_replace(['&quot;', '\"', '\\\'', '\'', '"', '\\', '%', '*', '?', ' '], '', (string) $value);
		$value = round((float) str_replace(',', '.', $value), 2);

		return $value;
	}//end balance()

	private function cost(mixed $value) : float
	{
		return $this->balance($value);
	}//end cost()

	private function date(string $date) : string
	{
		$now = date('Y.m.d');
		if ($date == '')
		{
			return $now;
		}

		$date = str_replace(['-', ' '], ['.', ''], $date);

		$dat = explode('.', $date);

		if (! isset($dat[1]))
		{
			return $now;
		}

		$date = (strlen($dat[0]) == 2) ? $dat[2].'.'.$dat[1].'.'.$dat[0] : $dat[0].'.'.$dat[1].'.'.$dat[2];

		return $date;
	}//end date()

}//end class
