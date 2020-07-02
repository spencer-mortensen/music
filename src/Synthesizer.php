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

use SpencerMortensen\Music\Instruments\MarimbaInstrument;
use SpencerMortensen\Music\Wave\Writer;

class Synthesizer
{
	/** @var int */
	private $rate;

	/** @var array */
	private $cache;

	public function synthesize(array $notes, $path, bool $isSong)
	{
		$channels = 1;
		$quality = 2;
		$this->rate = 44100;

		$writer = new Writer();
		$writer->open($path, $channels, $quality, $this->rate);

		if ($isSong) {
			$silence = $this->getSilence(.5);
			$writer->write($silence);
		}

		$instrument = new MarimbaInstrument();

		$song = [];
		$songTime = 0;

		foreach ($notes as $note) {
			list($time, $frequency, $spread) = $note;

			if ($songTime < $time) {
				$duration = $time - $songTime;
				$length = (int)($this->rate * $duration);

				$samples = array_splice($song, 0, $length);
				$writer->write($samples);

				$silenceLength = $length - count($samples);

				if (0 < $silenceLength) {
					$silence = array_fill(0, $silenceLength, 0);
					$writer->write($silence);
				}

				$songTime = $time;
			}

			$sample = $this->getSample($instrument, $frequency, $spread);
			$song = $this->add($song, $sample);
		}

		$writer->write($song);
		$writer->close();
	}

	private function getSilence($duration)
	{
		$length = (int)($this->rate * $duration);
		return array_fill(0, $length, 0);
	}

	private function getSample($instrument, $frequency, $spread)
	{
		$key = "{$frequency}:{$spread}";
		$sample = &$this->cache[$key];

		if (!isset($sample)) {
			$sample = $this->synthesizeSample($instrument, $frequency, $spread);
		}

		return $sample;
	}

	private function synthesizeSample($instrument, $frequency, $duration)
	{
		$samples = [];

		for ($i = 0; $instrument->get($frequency, $i / $this->rate, $duration, $sample); ++$i) {
			$samples[] = $sample;
		}

		return $samples;
	}

	private function add(array $songSamples, array $soundSamples)
	{
		foreach ($soundSamples as $i => $soundSample) {
			$songSample = &$songSamples[$i];
			$songSample += $soundSample / 4;
		}

		return $songSamples;
	}
}
