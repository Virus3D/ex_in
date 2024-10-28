<?php

namespace app;

use Twig_Loader_Filesystem;
use Twig_Environment;
use Twig\Extra\Intl\IntlExtension;

class template
{

	/** @var Twig_Environment */
	private $twig;

	/** @var array */
	private $var = [];

	public function __construct(string $path)
	{
		$loader     = new Twig_Loader_Filesystem($path);
		$this->twig = new Twig_Environment($loader);
		$this->twig->addExtension(new IntlExtension());
	}//end __construct()

	public function assign_vars(array $vars)
	{
		$this->var = array_merge($this->var, $vars);
	}//end assign_vars()

	public function render(string $tmpl) : string
	{
		return $this->twig->render($tmpl, $this->var);
	}//end render()

}//end class
