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

namespace SpencerMortensen\Music\Instruments;

class MarimbaInstrument implements Instrument
{
	public function get($frequency, $t, $duration, &$value): bool
	{
		$duration = 1;

		if ($duration < $t) {
			return false;
		}

		$angle = $frequency * 2 * M_PI * $t;

		$value =
			sin($angle / 2) / 64 +
			sin($angle) +
			sin($angle * 2) / 32 +
			sin($angle * 4) / 64;

		$value *= (pow(8, 1 - ($t / $duration)) - 1) / 7;

		return true;
	}
}
