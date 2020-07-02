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

class Reader
{
	private static $riff = 0x52494646; // "RIFF"
	private static $wave = 0x57415645; // "WAVE"
	private static $fmt = 0x666d7420; // "fmt "
	private static $data = 0x64617461; // "data"
	private static $pcmSize = 16;
	private static $pcmType = 1;

	private $file;
	private $channels; // 1: Mono, 2: Stereo
	private $quality; // 1: 8-bit, 2: 16-bit
	private $rate; // 44100: CD quality
	private $fileSize; // total size of the file (bytes)
	private $samples; // raw sample data

	public function read($path, &$channels, &$quality, &$rate, &$samples)
	{
		$file = fopen($path, 'r');

		if (!is_resource($file)) {
			return false;
		}

		$fileSize = filesize($path);

		if (!is_int($fileSize)) {
			return false;
		}

		$this->file = $file;
		$this->fileSize = $fileSize;

		$this->readChunkDescriptor() &&
		$this->readFormatSubChunk() &&
		$this->readDataHeader() &&
		$this->readData();

		fclose($this->file);

		$channels = $this->channels;
		$quality = $this->quality;
		$rate = $this->rate;
		$samples = $this->samples;

		return true;
	}

	private function readChunkDescriptor()
	{
		$binary = fread($this->file, 12);
		$values = unpack('Nid/Vsize/Nformat', $binary);

		return ($values !== false) &&
			($values['id'] === self::$riff) &&
			($values['format'] === self::$wave);
	}

	private function readFormatSubChunk()
	{
		$binary = fread($this->file, 24);
		$values = unpack('Nid/Vsize/vaudioFormat/vchannels/VsampleRate/VbyteRate/vblockAlign/vbitsPerSample', $binary);

		if ($values === false) {
			return false;
		}

		$this->channels = $values['channels']; // 1 = Mono, 2 = Stereo
		$this->quality = $values['bitsPerSample'] >> 3; // 1: 8-bit, 2: 16-bit
		$this->rate = $values['sampleRate']; // e.g. CD quality: 44100

		return ($values['id'] === self::$fmt) &&
			($values['size'] === self::$pcmSize) &&
			($values['audioFormat'] === self::$pcmType);
	}

	private function readDataHeader()
	{
		$binary = fread($this->file, 8);
		$values = unpack('Nid/Vsize', $binary);

		return ($values !== false) &&
			($values['id'] === self::$data);
	}

	private function readData()
	{
		fseek($this->file, 44);
		$dataSize = $this->fileSize - 44;
		$binary = fread($this->file, $dataSize);

		$samplesCount = $dataSize / $this->quality;

		if ($this->quality === 1) {
			$format = "C{$samplesCount}";
		} else {
			$format = "v{$samplesCount}";
		}

		$values = unpack($format, $binary);

		if ($values === false) {
			return false;
		}

		$this->samples = $this->getSamples($values);
		return true;
	}

	private function getSamples(array $values)
	{
		$type = (($this->quality - 1) << 1) | ($this->channels - 1);

		switch ($type) {
			case 0:
				return $this->getLowQualityMonoSamples($values);

			case 1:
				return $this->getLowQualityStereoSamples($values);

			case 2:
				return $this->getHighQualityMonoSamples($values);

			default: // 3:
				return $this->getHighQualityStereoSamples($values);
		}
	}

	private function getLowQualityMonoSamples($values)
	{
		return array_values($values);
	}

	private function getLowQualityStereoSamples($values)
	{
		$samples = array();

		for ($i = 0, $n = count($values); $i < $n; $i += 2) {
			$samples[] = array($values[$i], $values[$i + 1]);
		}

		return $samples;
	}

	private function getHighQualityMonoSamples($values)
	{
		$samples = array();
		$fill = (-1) << 16;

		foreach ($values as $i => $value) {
			$samples[] = self::fill($value, $fill); // from -2^15 to (2^15) - 1
		}

		return $samples;
	}

	private function getHighQualityStereoSamples($raw)
	{
		$samples = array();
		$fill = (-1) << 16;

		for ($i = 0, $n = count($raw); $i < $n; $i += 2) {
			$left = self::fill($raw[$i], $fill);
			$right = self::fill($raw[$i + 1], $fill);
			$samples[] = array($left, $right);
		}

		return $samples;
	}

	private static function fill($n, $fill)
	{
		return (0x8000 < $n) ? ($fill | $n) : $n;
	}
}
