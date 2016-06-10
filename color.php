<?php
class Color
{
	private static $colorlist = [
		8 => [
			"#0000FF",
			"#009FDF",
			"#40C000",
			"#FFC000",
			"#DF5F00",
			"#C00000",
			"#C000C0",
			"#6000C0",
		],
		64 => [
			"#0000FF",
			"#0019FF",
			"#0032FF",
			"#004AFF",
			"#0063FF",
			"#007BFF",
			"#0095FF",
			"#00ADFF",
			"#00C6FF",
			"#00DEFF",
			"#00F8FF",
			"#00FFEC",
			"#00FFD1",
			"#00FFB8",
			"#00FF9C",
			"#00FF82",
			"#00FF67",
			"#00FF4D",
			"#00FF2A",
			"#00FF0F",
			"#0EFF00",
			"#30FF00",
			"#52FF00",
			"#71FF00",
			"#93FF00",
			"#B3FF00",
			"#D4FF00",
			"#F5FF00",
			"#FFF900",
			"#FFEF00",
			"#FFE500",
			"#FFDC00",
			"#FFD300",
			"#FFC900",
			"#FFC000",
			"#FFB600",
			"#FFAD00",
			"#FFA300",
			"#FF9A00",
			"#FF8E00",
			"#FF8000",
			"#FF7300",
			"#FF6500",
			"#FF5700",
			"#FF4900",
			"#FF3C00",
			"#FF2E00",
			"#FF2000",
			"#FF1300",
			"#FF0500",
			"#FF001F",
			"#FF004E",
			"#FF007D",
			"#FF00AB",
			"#FF00DB",
			"#F900FF",
			"#DF00FF",
			"#C600FF",
			"#AC00FF",
			"#9300FF",
			"#7A00FF",
			"#6000FF",
			"#4600FF",
			"#2D00FF"
		]
	];

	/**
	 * Returns a color from the list
	 * @param  int         $id      The color index.
	 * @param  int         $palette The palette from which to get the color.
	 * @return bool|string False on failure, hex color on success.
	 */
	public static function getColor($id, $palette = 64)
	{
		// Make sure the palette is valid
		if (!isset(self::$colorlist[$palette]))
		{
			return false;
		}

		$colors = self::$colorlist[$palette];

		// Make sure color is within palette bounds
		if (!isset($colors[$id]))
		{
			return false;
		}

		return $colors[$id];
	}
}
