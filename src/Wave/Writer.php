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

namespace SpencerMortensen\Music\Wave;

use Exception;

/*
 * @see http://soundfile.sapp.org/doc/WaveFormat File format
 */
class Writer
{
	private static $riff = 0x52494646; // "RIFF"
	private static $wave = 0x57415645; // "WAVE"
	private static $fmt = 0x666d7420; // "fmt "
	private static $data = 0x64617461; // "data"
	private static $pcmSize = 16;
	private static $pcmType = 1;

	/** @var resource */
	private $file;

	/** @var int */
	private $channels;

	/** @var int */
	private $quality;

	/** @var int */
	private $rate;

	/** @var int */
	private $fileSize;

	/**
	 * @param string $path
	 * Path to the recording file
	 *
	 * @param int $channels
	 * 1: Mono, 2: Stereo
	 *
	 * @param $quality
	 * 1: 8-bit, 2: 16-bit
	 *
	 * @param $rate
	 * 44100: CD quality
	 *
	 * @throws Exception;
	 */
	public function open($path, $channels, $quality, $rate)
	{
		if (!self::validSampleChannels($channels)) {
			throw new Exception('Invalid channels');
		}

		if (!self::validSampleSize($quality)) {
			throw new Exception('Invalid sample size');
		}

		if (!self::validSampleRate($rate)) {
			throw new Exception('Invalid sample rate');
		}

		$this->file = null;
		$this->channels = $channels;
		$this->quality = $quality;
		$this->rate = $rate;

		$this->file = fopen($path, 'w');
		$this->fileSize = 0;
		$this->writeChunkDescriptor();
		$this->writeFormatSubChunk();
		$this->writeDataHeader();
	}

	private static function validSampleChannels($input)
	{
		return ($input === 1) || ($input === 2);
	}

	private static function validSampleSize($input)
	{
		return ($input === 1) || ($input === 2);
	}

	private static function validSampleRate($input)
	{
		return is_int($input) && (0 < $input);
	}

	private function writeChunkDescriptor()
	{
		$binary = pack('NVN', self::$riff, 0, self::$wave);
		$this->add($binary);
	}

	private function writeFormatSubChunk()
	{
		$bitsPerSample = $this->quality << 3;
		$blockAlign = $this->channels * $this->quality;
		$byteRate = $blockAlign * $this->rate;

		$binary = pack('NVvvVVvv', self::$fmt, self::$pcmSize, self::$pcmType, $this->channels, $this->rate, $byteRate, $blockAlign, $bitsPerSample);
		return $this->add($binary);
	}

	private function writeDataHeader()
	{
		$binary = pack('NV', self::$data, 0);
		return $this->add($binary);
	}

	public function write(array $values)
	{
		$maxAmplitude = 1 << (($this->quality * 8) - 2);

		$data = ['v*'];

		foreach ($values as $value) {
			$data[] = (int)round($value * $maxAmplitude);
		}

		$binary = call_user_func_array('pack', $data);
		return $this->add($binary);
	}

	private function add($binary)
	{
		$length = strlen($binary);
		$this->fileSize += $length;
		return fwrite($this->file, $binary, $length) === $length;
	}

	public function close()
	{
		$this->updateChunkDescriptor();
		$this->updateDataHeader();

		fclose($this->file);
	}

	private function updateChunkDescriptor()
	{
		fseek($this->file, 4);
		$binary = pack('V', $this->fileSize - 8);
		return fwrite($this->file, $binary, 4) === 4;
	}

	private function updateDataHeader()
	{
		fseek($this->file, 40);
		$binary = pack('V', $this->fileSize - 44);
		return fwrite($this->file, $binary, 4);
	}
}
