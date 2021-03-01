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

class Scorer
{
	/** @var Input */
	private $input;

	/** @var array */
	private $notes;

	/** @var int */
	private $time;

	/** @var int */
	private $speed;

	/** @var array */
	private $score;

	public function getScore(string $input)
	{
		$this->notes = [];
		$this->time = 0;
		$this->speed = 1;
		$this->score = [];

		$this->input = new Input(trim($input));

		$this->readNoteFamilies() &&
		$this->readInstructions();

		return $this->score;
	}

	private function readNoteFamilies()
	{
		while ($this->readNoteFamily($name, $frequencies)) {
			$this->notes[$name] = $frequencies;
		}

		return true;
	}

	private function readNoteFamily(&$name, &$frequencies)
	{
		return $this->readNoteFamilyName($name) &&
			$this->readNoteFamilyFrequencies($frequencies);
	}

	private function readNoteFamilyName(&$name)
	{
		return $this->input->get('([a-zA-Z]):\\s*', $name);
	}

	private function readNoteFamilyFrequencies(&$frequencies)
	{
		$frequencies = [];

		while ($this->readFrequency($frequency)) {
			$frequencies[] = $frequency;
		}

		return 0 < count($frequencies);
	}

	private function readFrequency(&$frequency)
	{
		if ($this->input->get('([0-9.]+)\\s+', $match)) {
			$frequency = self::getNumber($match);
			return true;
		}

		return false;
	}

	private function readInstructions()
	{
		while ($this->readInstruction());

		return true;
	}

	private function readInstruction()
	{
		return $this->readSpeed() ||
			$this->readSingleNote() ||
			$this->readChord() ||
			$this->readRest();
	}

	private function readSpeed()
	{
		if ($this->input->get('speed:\\s*([0-9.]+)\\s+', $match)) {
			$this->speed = self::getNumber($match);
			return true;
		}

		return false;
	}

	private function readSingleNote()
	{
		if (!$this->readNote()) {
			return false;
		}

		$this->time += (1 / $this->speed);
		return true;
	}

	private function readNote()
	{
		if (!$this->input->get('(?<name>[a-zA-Z])(?<i>[0-9]+)(?:\\+(?<beats>[0-9]+))?\\s*', $match)) {
			return false;
		}

		$name = $match['name'];

		if (!isset($this->notes[$name])) {
			return false;
		}

		$i = (int)$match['i'];
		$frequency = $this->getFrequency($name, $i);
		$beats = isset($match['beats']) ? (int)$match['beats'] : 1;
		$duration = $beats / $this->speed;

		$this->score[] = [$this->time, $frequency, $duration];
		return true;
	}

	private function getFrequency(string $name, int $i)
	{
		$frequencies = $this->notes[$name];
		$n = count($frequencies);
		return $frequencies[$i % $n] * (1 << (int)floor($i / $n));
	}

	private function readChord()
	{
		if (!$this->input->get('\\(')) {
			return false;
		}

		while ($this->readNote());

		if (!$this->input->get('\\)\\s*')) {
			return false;
		}

		$this->time += (1 / $this->speed);
		return true;
	}

	private function readRest()
	{
		if (!$this->input->get('\\.\\s+')) {
			return false;
		}

		$this->time += (1 / $this->speed);
		return true;
	}

	private static function getNumber(string $text)
	{
		$number = (int)$text;

		if ((string)$number === $text) {
			return $number;
		}

		return (float)$text;
	}
}
