<?php

/**
 * Copyright (C) 2020 Spencer Mortensen
 *
 * This file is part of Music.
 *
 * Music is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Music is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Music. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Spencer Mortensen
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL-3.0
 * @copyright 2020 Spencer Mortensen
 */

namespace SpencerMortensen\Music;

class Input
{
	/** @var string */
	private $input;

	/** @var integer */
	private $position;

	public function __construct($input)
	{
		$this->input = $input;
		$this->position = 0;
	}

	public function get($expression, &$value = null)
	{
		$pattern = "\x03{$expression}\x03AXDs";

		if (preg_match($pattern, $this->input, $matches, 0, $this->position) !== 1) {
			return false;
		}

		$value = $matches;

		if (2 < count($matches)) {
			unset($value[0]);
		} else {
			$value = array_pop($value);
		}

		$length = strlen($matches[0]);

		$this->position += $length;
		return true;
	}

	public function getString($string)
	{
		$length = strlen($string);

		if (strlen($this->input) < $this->position + $length) {
			return false;
		}

		$length = strlen($string);

		if (substr_compare($this->input, $string, $this->position, $length) !== 0) {
			return false;
		}

		$this->position += $length;
		return true;
	}

	public function getPosition()
	{
		return $this->position;
	}

	public function setPosition($position)
	{
		$this->position = $position;
	}

	public function move($offset)
	{
		$this->position += $offset;
	}

	public function isHalted()
	{
		return strlen($this->input) <= $this->position;
	}
}
