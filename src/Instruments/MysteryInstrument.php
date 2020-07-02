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

class MysteryInstrument
{
	public function get($frequency, $t, &$value)
	{
		$duration = 1;

		if ($duration < $t) {
			return false;
		}

		if ($frequency === null) {
			$value = 0;
			return true;
		}

		$angle = $frequency * 2 * M_PI * $t;

		$a0 = pow($t, -$t) / 1.13498;
		$a1 = pow(2 * $t, -2 * $t) / 1.2421;
		$a2 = pow(4 * $t, -4 * $t) / 1.32825;

		$note =
			sin($angle / 2) * $a0 / 64 +
			sin($angle) * $a1 +
			sin($angle * 2) * $a2 / 16 +
			sin($angle * 4) * $a2 / 32;


		$value = $note * ($duration - $t);
		return true;
	}
}
