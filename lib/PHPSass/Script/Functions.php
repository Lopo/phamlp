<?php
namespace PHPSass\Script;

/**
 * Script functions class file.
 *
 * Methods in this module are accessible from the Script context.
 * For example, you can write:
 * $colour = hsl(120, 100%, 50%)
 * and it will call Functions::hsl().
 *
 * There are a few things to keep in mind when modifying this module.
 * First of all, the arguments passed are Literal objects.
 * Literal objects are also expected to be returned.
 *
 * Most Literal objects support the Literal->value accessor
 * for getting their values. Colour objects, though, must be accessed using Colour::rgb().
 *
 * Second, making functions accessible from Sass introduces the temptation
 * to do things like database access within stylesheets.
 * This temptation must be resisted.
 * Keep in mind that Sass stylesheets are only compiled once and then left as
 * static CSS files. Any dynamic CSS should be left in <style> tags in the HTML.
 *
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 */

/**
 * Script functions class.
 * A collection of functions for use in Script.
 */
class Functions
{
	const DECREASE=FALSE;
	const INCREASE=TRUE;

	public static $parser=FALSE;


	/**
	 * @param Literals\SassString $name
	 * @return Literals\Boolean|Literals\SassString
	 */
	public static function option($name)
	{
		$options=\PHPSass\Parser::$instance->getOptions();
		if (isset($options[$name->value])) {
			return new Literals\SassString($options[$name->value]);
			}

		return new Literals\Boolean(FALSE);
	}

	/*
	 * Colour Creation
	 */

	/**
	 * Creates a Colour object from red, green, and blue values.
	 *
	 * @param Literals\Number $red the red component.
	 * A number between 0 and 255 inclusive, or between 0% and 100% inclusive
	 * @param Literals\Number $green the green component.
	 * A number between 0 and 255 inclusive, or between 0% and 100% inclusive
	 * @param Literals\Number $blue the blue component.
	 * A number between 0 and 255 inclusive, or between 0% and 100% inclusive
	 * @return Literals\Colour new Colour object
	 */
	public static function rgb($red, $green, $blue)
	{
		return self::rgba($red, $green, $blue, new Literals\Number(1));
	}

	/**
	 * Creates a Colour object from red, green, and blue values and alpha channel (opacity).
	 * There are two overloads:
	 * * rgba(red, green, blue, alpha)
	 * @param Literals\Number the red component.
	 * A number between 0 and 255 inclusive, or between 0% and 100% inclusive
	 * @param Literals\Number the green component.
	 * A number between 0 and 255 inclusive, or between 0% and 100% inclusive
	 * @param Literals\Number the blue component.
	 * A number between 0 and 255 inclusive, or between 0% and 100% inclusive
	 * @param Literals\Number The alpha channel. A number between 0 and 1.
	 *
	 * * rgba(colour, alpha)
	 * @param Literals\Colour a Colour object
	 * @param Literals\Number The alpha channel. A number between 0 and 1.
	 *
	 * @return Literals\Colour new Colour object
	 * @throws ScriptFunctionException if any of the red, green, or blue
	 * colour components are out of bounds, or or the colour is not a colour, or alpha is out of bounds
	 */
	public static function rgba()
	{
		switch (func_num_args()) {
			case 2:
				$colour=func_get_arg(0);
				$alpha=func_get_arg(1);
				Literals\Literal::assertType($colour, 'Colour');
				Literals\Literal::assertType($alpha, 'Number');
				Literals\Literal::assertInRange($alpha, 0, 1);

				return $colour->with(['alpha' => $alpha->value]);
			case 4:
				$rgba=[];
				$components=func_get_args();
				$alpha=array_pop($components);
				foreach ($components as $component) {
					Literals\Literal::assertType($component, 'Number');
					if ($component->units=='%') {
						Literals\Literal::assertInRange($component, 0, 100, '%');
						$rgba[]=$component->value*2.55;
						}
					else {
						Literals\Literal::assertInRange($component, 0, 255);
						$rgba[]=$component->value;
						}
					}
				Literals\Literal::assertType($alpha, 'Number');
				Literals\Literal::assertInRange($alpha, 0, 1);
				$rgba[]=$alpha->value;

				return new Literals\Colour($rgba);
			default:
				throw new ScriptFunctionException('Incorrect argument count for '.__METHOD__.'; expected 2 or 4, received '.func_num_args(), Parser::$context->node);
			}
	}

	/**
	 * Creates a Colour object from hue, saturation, and lightness.
	 * Uses the algorithm from the {@link http://www.w3.org/TR/css3-colour/#hsl-colour CSS3 spec}.
	 *
	 * @param float $h The hue of the colour in degrees.
	 * Should be between 0 and 360 inclusive
	 * @param mixed $s The saturation of the colour as a percentage.
	 * Must be between '0%' and 100%, inclusive
	 * @param mixed $l The lightness of the colour as a percentage.
	 * Must be between 0% and 100%, inclusive
	 * @return Literals\Colour new The resulting colour
	 */
	public static function hsl($h, $s, $l)
	{
		Literals\Literal::assertInRange($s, 0, 100, '%');
		Literals\Literal::assertInRange($l, 0, 100, '%');

		return self::hsla($h, $s, $l, new Literals\Number(1));
	}

	/**
	 * Creates a Colour object from hue, saturation, lightness and alpha channel (opacity).
	 *
	 * @param Literals\Number $h The hue of the colour in degrees.
	 * Should be between 0 and 360 inclusive
	 * @param Literals\Number $s The saturation of the colour as a percentage.
	 * Must be between 0% and 100% inclusive
	 * @param Literals\Number $l The lightness of the colour as a percentage.
	 * Must be between 0% and 100% inclusive
	 * @param float $a The alpha channel. A number between 0 and 1.
	 * @return Literals\Colour new The resulting colour
	 */
	public static function hsla($h, $s, $l, $a)
	{
		Literals\Literal::assertType($h, 'Number');
		Literals\Literal::assertType($s, 'Number');
		Literals\Literal::assertType($l, 'Number');
		Literals\Literal::assertType($a, 'Number');
		Literals\Literal::assertInRange($s, 0, 100, '%');
		Literals\Literal::assertInRange($l, 0, 100, '%');
		Literals\Literal::assertInRange($a, 0, 1);

		return new Literals\Colour(['hue' => $h, 'saturation' => $s, 'lightness' => $l, 'alpha' => $a]);
	}

	/*
	 * Colour Information
	 */

	/**
	 * Returns the red component of a colour.
	 *
	 * @param Literals\Colour $colour The colour
	 * @return Literals\Number The red component of colour
	 */
	public static function red($colour)
	{
		Literals\Literal::assertType($colour, 'Colour');

		return new Literals\Number($colour->red);
	}

	/**
	 * Returns the green component of a colour.
	 *
	 * @param Literals\Colour $colour The colour
	 * @return Literals\Number The green component of colour
	 */
	public static function green($colour)
	{
		Literals\Literal::assertType($colour, 'Colour');

		return new Literals\Number($colour->green);
	}

	/**
	 * Returns the blue component of a colour.
	 *
	 * @param Literals\Colour $colour The colour
	 * @return Literals\Number The blue component of colour
	 */
	public static function blue($colour)
	{
		Literals\Literal::assertType($colour, 'Colour');

		return new Literals\Number($colour->blue);
	}

	/**
	 * Returns the hue component of a colour.
	 *
	 * @param Literals\Colour $colour The colour
	 * @return Literals\Number The hue component of colour
	 */
	public static function hue($colour)
	{
		Literals\Literal::assertType($colour, 'Colour');

		return new Literals\Number($colour->getHue().'deg');
	}

	/**
	 * Returns the saturation component of a colour.
	 *
	 * @param Literals\Colour $colour The colour
	 * @return Literals\Number The saturation component of colour
	 */
	public static function saturation($colour)
	{
		Literals\Literal::assertType($colour, 'Colour');

		return new Literals\Number($colour->getSaturation().'%');
	}

	/**
	 * Returns the lightness component of a colour.
	 *
	 * @param Literals\Colour $colour The colour
	 * @return Literals\Number The lightness component of colour
	 */
	public static function lightness($colour)
	{
		Literals\Literal::assertType($colour, 'Colour');

		return new Literals\Number($colour->getLightness().'%');
	}

	/**
	 * Returns the alpha component (opacity) of a colour.
	 *
	 * @param Literals\Colour $colour The colour
	 * @return mixed Literals\Number The alpha component (opacity) of colour
	 *
	 * RL modified so that the filter: alpha function doesn't bork
	 */
	public static function alpha($colour)
	{
		try {
			Literals\Literal::assertType($colour, 'Colour');
			}
		catch (\Exception $e) {
			return new Literals\SassString('alpha(100)');
			}

		return new Literals\Number($colour->alpha);
	}

	/**
	 * Returns the alpha component (opacity) of a colour.
	 *
	 * @param Literals\Colour $colour The colour
	 * @return Literals\Number The alpha component (opacity) of colour
	 */
	public static function opacity($colour)
	{
		Literals\Literal::assertType($colour, 'Colour');

		return new Literals\Number($colour->alpha);
	}

	/*
	 * Colour Adjustments
	 */

	/**
	 * Changes the hue of a colour while retaining the lightness and saturation.
	 *
	 * @param Literals\Colour $colour The colour to adjust
	 * @param Literals\Number $degrees The amount to adjust the colour by
	 * @return Literals\Colour The adjusted colour
	 */
	public static function adjust_hue($colour, $degrees)
	{
		Literals\Literal::assertType($colour, 'Colour');
		Literals\Literal::assertType($degrees, 'Number');

		return $colour->with(['hue' => $colour->getHue(TRUE)+$degrees->value]);
	}

	/**
	 * Changes the tint of a colour, mixing it with the $amount of white.
	 *
	 * @param Literals\Colour $colour The colour to adjust
	 * @param Literals\Number $amount The amount of white to mix with the $colour (not a number)
	 * @return Literals\Colour The adjusted colour
	 */
	public static function tint($colour, $amount)
	{
		return self::mix(new Literals\Colour('white'), $colour, $amount);
	}

	/**
	 * Changes the shade of a colour, mixing it with the $amount of black.
	 *
	 * @param Literals\Colour $colour The colour to adjust
	 * @param Literals\Number $amount The amount of black to mix with the $colour (not a number)
	 * @return Literals\Colour The adjusted colour
	 */
	public static function shade($colour, $amount)
	{
		return self::mix(new Literals\Colour('black'), $colour, $amount);
	}

	/**
	 * Makes a colour lighter.
	 *
	 * @param Literals\Colour $colour The colour to lighten
	 * @param Literals\Number $amount The amount to lighten the colour by
	 * @param Literals\Boolean $ofCurrent Whether the amount is a proportion of the current value (true) or the total range (false).
	 * The default is false - the amount is a proportion of the total range.
	 * If the colour lightness value is 40% and the amount is 50%,
	 * the resulting colour lightness value is 90% if the amount is a proportion
	 * of the total range, whereas it is 60% if the amount is a proportion of the current value.
	 * @return Literals\Colour The lightened colour
	 * @see lighten_rel
	 */
	public static function lighten($colour, $amount, $ofCurrent=FALSE)
	{
		return self::adjust($colour, $amount, $ofCurrent, 'lightness', self::INCREASE, 0, 100, '%');
	}

	/**
	 * Makes a colour darker.
	 *
	 * @param Literals\Colour $colour The colour to darken
	 * @param Literals\Number $amount The amount to darken the colour by
	 * @param Literals\Boolean $ofCurrent Whether the amount is a proportion of the current value (true) or the total range (false).
	 * The default is false - the amount is a proportion of the total range.
	 * If the colour lightness value is 80% and the amount is 50%,
	 * the resulting colour lightness value is 30% if the amount is a proportion
	 * of the total range, whereas it is 40% if the amount is a proportion of the current value.
	 * @return Literals\Colour The darkened colour
	 * @see adjust
	 */
	public static function darken($colour, $amount, $ofCurrent=FALSE)
	{
		return self::adjust($colour, $amount, $ofCurrent, 'lightness', self::DECREASE, 0, 100, '%');
	}

	/**
	 * Makes a colour more saturated.
	 *
	 * @param Literals\Colour $colour The colour to saturate
	 * @param Literals\Number $amount The amount to saturate the colour by
	 * @param Literals\Boolean $ofCurrent Whether the amount is a proportion of the current value (true) or the total range (false).
	 * The default is false - the amount is a proportion of the total range.
	 * If the colour saturation value is 40% and the amount is 50%,
	 * the resulting colour saturation value is 90% if the amount is a proportion
	 * of the total range, whereas it is 60% if the amount is a proportion of the current value.
	 * @return Literals\Colour The saturated colour
	 * @see adjust
	 */
	public static function saturate($colour, $amount, $ofCurrent=FALSE)
	{
		return self::adjust($colour, $amount, $ofCurrent, 'saturation', self::INCREASE, 0, 100, '%');
	}

	/**
	 * Makes a colour less saturated.
	 *
	 * @param Literals\Colour $colour The colour to desaturate
	 * @param Literals\Number $amount The amount to desaturate the colour by
	 * @param Literals\Boolean $ofCurrent Whether the amount is a proportion of the current value (true) or the total range (false).
	 * The default is false - the amount is a proportion of the total range.
	 * If the colour saturation value is 80% and the amount is 50%,
	 * the resulting colour saturation value is 30% if the amount is a proportion
	 * of the total range, whereas it is 40% if the amount is a proportion of the current value.
	 * @return Literals\Colour The desaturateed colour
	 * @see adjust
	 */
	public static function desaturate($colour, $amount, $ofCurrent=FALSE)
	{
		return self::adjust($colour, $amount, $ofCurrent, 'saturation', self::DECREASE, 0, 100, '%');
	}

	/**
	 * Makes a colour more opaque.
	 *
	 * @param Literals\Colour $colour The colour to opacify
	 * @param Literals\Number $amount The amount to opacify the colour by
	 * @param Literals\Boolean $ofCurrent
	 * If this is a unitless number between 0 and 1 the adjustment is absolute,
	 * if it is a percentage the adjustment is relative.
	 * If the colour alpha value is 0.4
	 * if the amount is 0.5 the resulting colour alpha value  is 0.9,
	 * whereas if the amount is 50% the resulting colour alpha value  is 0.6.
	 * @return Literals\Colour The opacified colour
	 * @see opacify_rel
	 */
	public static function opacify($colour, $amount, $ofCurrent=FALSE)
	{
		$units=self::units($amount);

		return self::adjust($colour, $amount, $ofCurrent, 'alpha', self::INCREASE, 0, ($units==='%'? 100 : 1), $units);
	}

	/**
	 * Makes a colour more transparent.
	 *
	 * @param Literals\Colour $colour The colour to transparentize
	 * @param Literals\Number $amount The amount to transparentize the colour by.
	 * @param Literals\Boolean $ofCurrent
	 * If this is a unitless number between 0 and 1 the adjustment is absolute,
	 * if it is a percentage the adjustment is relative.
	 * If the colour alpha value is 0.8
	 * if the amount is 0.5 the resulting colour alpha value  is 0.3,
	 * whereas if the amount is 50% the resulting colour alpha value  is 0.4.
	 * @return Literals\Colour The transparentized colour
	 */
	public static function transparentize($colour, $amount, $ofCurrent=FALSE)
	{
		$units=self::units($amount);

		return self::adjust($colour, $amount, $ofCurrent, 'alpha', self::DECREASE, 0, ($units==='%'? 100 : 1), $units);
	}

	/**
	 * Makes a colour more opaque.
	 * Alias for {@link opacify}.
	 *
	 * @param Literals\Colour $colour The colour to opacify
	 * @param Literals\Number $amount The amount to opacify the colour by
	 * @param Literals\Boolean $ofCurrent Whether the amount is a proportion of the current value (true) or the total range (false).
	 * @return Literals\Colour The opacified colour
	 * @see opacify
	 */
	public static function fade_in($colour, $amount, $ofCurrent=FALSE)
	{
		return self::opacify($colour, $amount, $ofCurrent);
	}

	/**
	 * Makes a colour more transparent.
	 * Alias for {@link transparentize}.
	 *
	 * @param Literals\Colour $colour The colour to transparentize
	 * @param Literals\Number $amount The amount to transparentize the colour by
	 * @param Literals\Boolean $ofCurrent Whether the amount is a proportion of the current value (true) or the total range (false).
	 * @return Literals\Colour The transparentized colour
	 * @see transparentize
	 */
	public static function fade_out($colour, $amount, $ofCurrent=FALSE)
	{
		return self::transparentize($colour, $amount, $ofCurrent);
	}

	/**
	 * Returns the complement of a colour.
	 * Rotates the hue by 180 degrees.
	 *
	 * @param Literals\Colour $colour The colour
	 * @return Literals\Colour The comlemented colour
	 * @uses adjust_hue()
	 */
	public static function complement($colour)
	{
		// return self::adjust($colour, new Literals\Number('180deg'), TRUE, 'hue', self::INCREASE, 0, 360, '');
		return self::adjust_hue($colour, new Literals\Number('180deg'));
	}

	/**
	 * Greyscale for non-english speakers.
	 *
	 * @param Literals\Colour $colour The colour
	 * @return Literals\Colour The greyscale colour
	 * @see desaturate
	 */
	public static function grayscale($colour)
	{
		return self::desaturate($colour, new Literals\Number(100));
	}

	/**
	 * Converts a colour to greyscale.
	 * Reduces the saturation to zero.
	 *
	 * @param Literals\Colour $colour The colour
	 * @return Literals\Colour The greyscale colour
	 * @see desaturate
	 */
	public static function greyscale($colour)
	{
		return self::desaturate($colour, new Literals\Number(100));
	}

	/**
	 * Inverts a colour.
	 * The red, green, and blue values are inverted value = (255 - value)
	 *
	 * @param Literals\Colour $colour the colour
	 * @return Literals\Colour the inverted colour
	 */
	public static function invert($colour)
	{
		Literals\Literal::assertType($colour, 'Colour');

		return $colour->with([
				'red' => 255-$colour->getRed(TRUE),
				'blue' => 255-$colour->getBlue(TRUE),
				'green' => 255-$colour->getGreen(TRUE)
				]);
	}

	/**
	 * Mixes two colours together.
	 * Takes the average of each of the RGB components, optionally weighted by the
	 * given percentage. The opacity of the colours is also considered when
	 * weighting the components.
	 * The weight specifies the amount of the first colour that should be included
	 * in the returned colour. The default, 50%, means that half the first colour
	 * and half the second colour should be used. 25% means that a quarter of the
	 * first colour and three quarters of the second colour should be used.
	 * For example:
	 *   mix(#f00, #00f) => #7f007f
	 *   mix(#f00, #00f, 25%) => #3f00bf
	 *   mix(rgba(255, 0, 0, 0.5), #00f) => rgba(63, 0, 191, 0.75)
	 *
	 * @param Literals\Colour $colour1 The first colour
	 * @param Literals\Colour $colour2 The second colour
	 * @param float $weight Percentage of the first colour to use
	 * @return Literals\Colour The mixed colour
	 */
	public static function mix($colour1, $colour2, $weight='50%')
	{
		if (is_object($weight)) {
			$weight=new Literals\Number($weight);
			}
		Literals\Literal::assertType($colour1, 'Colour');
		Literals\Literal::assertType($colour2, 'Colour');
		Literals\Literal::assertType($weight, 'Number');
		Literals\Literal::assertInRange($weight, 0, 100, '%');
		/*
		 * This algorithm factors in both the user-provided weight
		 * and the difference between the alpha values of the two colours
		 * to decide how to perform the weighted average of the two RGB values.
		 *
		 * It works by first normalizing both parameters to be within [-1, 1],
		 * where 1 indicates "only use colour1", -1 indicates "only use colour 0",
		 * and all values in between indicated a proportionately weighted average.
		 *
		 * Once we have the normalized variables w and a,
		 * we apply the formula (w + a)/(1 + w*a)
		 * to get the combined weight (in [-1, 1]) of colour1.
		 * This formula has two especially nice properties:
		 *
		 * * When either w or a are -1 or 1, the combined weight is also that number
		 *  (cases where w * a == -1 are undefined, and handled as a special case).
		 *
		 * * When a is 0, the combined weight is w, and vice versa
		 *
		 * Finally, the weight of colour1 is renormalized to be within [0, 1]
		 * and the weight of colour2 is given by 1 minus the weight of colour1.
		 */

		$p=$weight->value/100;
		$w=$p*2-1;
		$a=$colour1->alpha-$colour2->alpha;


		$w1= ((($w*$a==-1)
			? $w
			: ($w+$a)/(1+$w*$a))+1)/2;
		$w2=1-$w1;

		$rgb1=$colour1->getRgb();
		$rgb2=$colour2->getRgb();
		$rgba=[];
		foreach ($rgb1 as $key => $value) {
			$rgba[$key]=floor(($value*$w1)+($rgb2[$key]*$w2));
			}
		$rgba[]=floor($colour1->alpha*$p+$colour2->alpha*(1-$p));

		return new Literals\Colour($rgba);
	}

	/**
	 * Adjusts one or more property of the color by the value requested.
	 *
	 * @param Literals\Colour $colour the colour to adjust
	 * @param Literals\Number $red (red, green, blue, hue, saturation, lightness, alpha) - the amount(s) to adjust by
	 * @param Literals\Number $green
	 * @param Literals\Number $blue
	 * @param Literals\Number $hue
	 * @param Literals\Number $saturation
	 * @param Literals\Number $lightness
	 * @param Literals\Number $alpha
	 * @return Literals\Colour
	 */
	public static function adjust_color($color, $red=0, $green=0, $blue=0, $hue=0, $saturation=0, $lightness=0, $alpha=0)
	{
		$properties=[
			'red' => $red,
			'green' => $green,
			'blue' => $blue,
			'hue' => $hue,
			'saturation' => $saturation,
			'lightness' => $lightness,
			'alpha' => $alpha
			];
		foreach ($properties as $name => $value) {
			$color=self::adjust($color, $value, FALSE, $name, self::INCREASE, 0, 255);
			}

		return $color;
	}

	/**
	 * Scales one or more property of the color by the percentage requested.
	 *
	 * @param Literals\Colour $colour the colour to adjust
	 * @param Literals\Number $red (red, green, blue, saturation, lightness, alpha) - the amount(s) to scale by
	 * @param Literals\Number $green
	 * @param Literals\Number $blue
	 * @param Literals\Number $hue
	 * @param Literals\Number $saturation
	 * @param Literals\Number $lightness
	 * @param Literals\Number $alpha
	 * @return Literals\Colour
	 */
	public static function scale_color($color, $red=0, $green=0, $blue=0, $saturation=0, $lightness=0, $alpha=0)
	{
		$maxes=[
			'red' => 255,
			'green' => 255,
			'blue' => 255,
			'saturation' => 100,
			'lightness' => 100,
			'alpha' => 1,
			];
		$color->rgb2hsl();
		foreach ($maxes as $property => $max) {
			$obj=$$property;
			$scale=0.01*$obj->value;
			$diff= $scale>0
				? $max-$color->$property
				: $color->$property;
			$color->$property=$color->$property+$diff*$scale;
			}
		$color->hsl2rgb();

		return $color;
	}

	/**
	 * Changes one or more properties of the color to the requested value
	 *
	 * @param Literals\Colour $colour - the color to change
	 * @param Literals\Number $red (red, green, blue, hue, saturation, lightness, alpha) - the amounts to scale by
	 * @param Literals\Number $green
	 * @param Literals\Number $blue
	 * @param Literals\Number $hue
	 * @param Literals\Number $saturation
	 * @param Literals\Number $lightness
	 * @param Literals\Number $alpha
	 * @return Literals\Colour
	 */
	public static function change_color($color, $red=FALSE, $green=FALSE, $blue=FALSE, $hue=FALSE, $saturation=FALSE, $lightness=FALSE, $alpha=FALSE)
	{
		$attrs=[];
		foreach (['red', 'green', 'blue', 'hue', 'saturation', 'lightness', 'alpha'] as $i => $property) {
			$obj=$$property;
			if ($obj instanceof Literals\Number) {
				$attrs[$property]=$obj->value;
				}
			}

		return $color->with($attrs);
	}

	/**
	 * Adjusts the colour
	 *
	 * @param Literals\Colour $colour the colour to adjust
	 * @param Literals\Number $amount the amount to adust by
	 * @param bool $ofCurrent whether the amount is a proportion of the current value or the total range
	 * @param string $att the attribute to adjust
	 * @param bool $op whether to decrease (FALSE) or increase (TRUE) the value of the attribute
	 * @param float $min minimum value the amount can be
	 * @param float $max maximum value the amount can bemixed
	 * @param string $units amount units
	 * @return Literals\Colour
	 */
	public static function adjust($colour, $amount, $ofCurrent, $att, $op, $min, $max, $units='')
	{
		Literals\Literal::assertType($colour, 'Colour');
		Literals\Literal::assertType($amount, 'Number');
		// Literals\Literal::assertInRange($amount, $min, $max, $units);
		if (!is_bool($ofCurrent)) {
			Literals\Literal::assertType($ofCurrent, 'Boolean');
			$ofCurrent=$ofCurrent->value;
			}
		$colour=clone $colour; # clone here to stop it altering original value

		$amount=$amount->value*(($att==='alpha' && $ofCurrent && $units==='')? 100 : 1);

		if ($att=='red'||$att=='blue'||$att=='green') {
			$colour->hsl2rgb();
			$colour->$att= $ofCurrent
				? $colour->$att*(1+($amount*($op===self::INCREASE? 1 : -1))/100)
				: $colour->$att+($amount*($op===self::INCREASE? 1 : -1));
			$colour->rgb2hsl();
			}
		else {
			$colour->rgb2hsl();
			$colour->$att= $ofCurrent
				? $colour->$att*(1+($amount*($op===self::INCREASE? 1 : -1))/100)
				: $colour->$att+($amount*($op===self::INCREASE? 1 : -1));
			$colour->$att=max($min, min($max, $colour->$att));
			$colour->hsl2rgb();
			}

		return $colour;
	}

	/*
	 * Number Functions
	 */

	/**
	 * Finds the absolute value of a number.
	 * For example:
	 *     abs(10px) => 10px
	 *     abs(-10px) => 10px
	 *
	 * @param Literals\Number $number The number to round
	 * @return Literals\Number The absolute value of the number
	 */
	public static function abs($number)
	{
		Literals\Literal::assertType($number, 'Number');

		return new Literals\Number(abs($number->value).$number->units);
	}

	/**
	 * Rounds a number up to the nearest whole number.
	 * For example:
	 *     ceil(10.4px) => 11px
	 *     ceil(10.6px) => 11px
	 *
	 * @param Literals\Number $number The number to round
	 * @return Literals\Number The rounded number
	 */
	public static function ceil($number)
	{
		Literals\Literal::assertType($number, 'Number');

		return new Literals\Number(ceil($number->value).$number->units);
	}

	/**
	 * Rounds down to the nearest whole number.
	 * For example:
	 *     floor(10.4px) => 10px
	 *     floor(10.6px) => 10px
	 *
	 * @param Literals\Number $number The number to round
	 * @return Literals\Number The rounded number
	 */
	public static function floor($number)
	{
		Literals\Literal::assertType($number, 'Number');

		return new Literals\Number(floor($number->value).$number->units);
	}

	/**
	 * Rounds a number to the nearest whole number.
	 * For example:
	 *     round(10.4px) => 10px
	 *     round(10.6px) => 11px
	 *
	 * @param Literals\Number $number The number to round
	 * @return Literals\Number The rounded number
	 */
	public static function round($number)
	{
		Literals\Literal::assertType($number, 'Number');

		return new Literals\Number(str_replace(',', '.', round($number->value)).$number->units);
	}

	/**
	 * Returns true if two numbers are similar enough to be added, subtracted, or compared.
	 *
	 * @param Literals\Number $number1 The first number to test
	 * @param Literals\Number $number2 The second number to test
	 * @return Literals\Boolean True if the numbers are similar
	 */
	public static function comparable($number1, $number2)
	{
		Literals\Literal::assertType($number1, 'Number');
		Literals\Literal::assertType($number2, 'Number');

		return new Literals\Boolean($number1->isComparableTo($number2));
	}

	/**
	 * Converts a decimal number to a percentage.
	 * For example:
	 *     percentage(100px / 50px) => 200%
	 *
	 * @param Literals\Number $number The decimal number to convert to a percentage
	 * @return Literals\Number The number as a percentage
	 */
	public static function percentage($number)
	{
		$number->value*=100;
		$number->units='%';

		return $number;
	}

	/**
	 * @return mixed
	 */
	public static function max()
	{
		$max=func_get_arg(0);
		foreach (func_get_args() as $var) {
			if ($var instanceOf Literals\Number && $var->op_gt($max)->value) {
				$max=$var;
				}
			}

		return $max;
	}

	/**
	 * @return mixed
	 */
	public static function min()
	{
		$min=func_get_arg(0);
		foreach (func_get_args() as $var) {
			if ($var instanceOf Literals\Number && $var->op_lt($min)->value) {
				$min=$var;
				}
			}

		return $min;
	}

	/**
	 * Inspects the unit of the number, returning it as a quoted string.
	 * Alias for units.
	 *
	 * @param Literals\Number $number The number to inspect
	 * @return Literals\SassString The units of the number
	 * @see units
	 */
	public static function unit($number)
	{
		return self::units($number);
	}

	/**
	 * Inspects the units of the number, returning it as a quoted string.
	 *
	 * @param Literals\Number $number The number to inspect
	 * @return Literals\SassString The units of the number
	 */
	public static function units($number)
	{
		Literals\Literal::assertType($number, 'Number');

		return new Literals\SassString($number->units);
	}

	/**
	 * Inspects the unit of the number, returning a boolean indicating if it is unitless.
	 *
	 * @param Literals\Number $number The number to inspect
	 * @return Literals\Boolean True if the number is unitless, false if it has units.
	 */
	public static function unitless($number)
	{
		Literals\Literal::assertType($number, 'Number');

		return new Literals\Boolean($number->isUnitless());
	}

	/*
	 * String Functions
	 */

	/**
	 * Add quotes to a string if the string isn't quoted, or returns the same string if it is.
	 *
	 * @param string $string String to quote
	 * @return Literals\SassString Quoted string
	 * @see unquote
	 */
	public static function quote($string)
	{
		Literals\Literal::assertType($string, 'SassString');

		return new Literals\SassString('"'.$string->value.'"');
	}

	/**
	 * Removes quotes from a string if the string is quoted, or returns the same string if it's not.
	 *
	 * @param string $string String to unquote
	 * @return Literals\SassString Unuoted string
	 * @see quote
	 */
	public static function unquote($string)
	{
		if ($string instanceof Literals\SassString) {
			return new Literals\SassString($string->value);
			}

		return $string;
	}

	/**
	 * Returns the variable whose name is the string.
	 *
	 * @param string $string String to unquote
	 * @return Literals\SassString
	 */
	public static function get_var($string)
	{
		Literals\Literal::assertType($string, 'SassString');

		return new Literals\SassString($string->toVar());
	}

	/**
	 * List Functions - taken mostly from Compass
	 */

	/**
	 * Returns the length of the $list
	 *
	 * @param Literals\List $list - the list to count
	 * @return Literals\Number
	 */
	public static function length($list)
	{
		if ($list instanceOf Literals\SassString) {
			$list=new Literals\SassList($list->toString());
			}

		return new Literals\Number($list->length());
	}

	/**
	 * Returns the nth value ofthe $list
	 *
	 * @param Literals\SassList $list - the list to get from
	 * @param Literals\Number $n - the value to get
	 * @return mixed
	 */
	public static function nth($list, $n)
	{
		Literals\Literal::assertType($n, 'Number');

		if ($list instanceof Literals\SassString) {
			$list=new Literals\SassList($list->toString());
			}

		return $list->nth($n->value);
	}

	/**
	 * @param mixed $one
	 * @param mixed $two
	 * @param string $sep
	 * @return Literals\SassList
	 */
	public static function join($one, $two, $sep=', ')
	{
		return self::append($one, $two, $sep);
	}

	/**
	 * @param mixed $list
	 * @param mixed $val
	 * @param string $sep
	 * @return Literals\SassList
	 */
	public static function append($list, $val, $sep=', ')
	{
		if ($list instanceOf Literals\SassString) {
			$list=new Literals\SassList($list->toString());
			}
		$list->append($val, $sep);

		return $list;
	}

	/**
	 * @param Literals\SassList $list
	 * @param mixed $value
	 * @return mixed
	 */
	public static function index($list, $value)
	{
		if (!($list instanceOf Literals\SassList)) {
			$list=new Literals\SassList($list->toString());
			}

		return $list->index($value);
	}

	/**
	 * New function zip allows several lists to be combined into one list of lists. For example: zip(1px 1px 3px, solid dashed solid, red green blue) becomes 1px solid red, 1px dashed green, 3px solid blue
	 *
	 * @return Literals\SassList
	 */
	public function zip()
	{
		$result=new Literals\SassList('', ',');
		foreach (func_get_args() as $i => $arg) {
			$list=new Literals\SassList($arg);
			foreach ($list->value as $j => $val) {
				$result->value+=[$j => new Literals\SassList('', 'space')];
				$result->value[$j]->value[]=(string)$val;
				}
			}

		return $result;
	}

	/*
	 * Misc. Functions
	 */

	/**
	 * An inline "if-else" statement.
	 *
	 * @param Literals\Boolean $condition - values are loosely-evaulated by PHP, so 'false' includes NULL, FALSE, 0, ''
	 * @param mixed $if_true - returns if Condition is TRUE
	 * @param mixed $if_false - returns if Condition is FALSE
	 * @return mixed
	 */
	public static function _if($condition, $if_true, $if_false)
	{
		return $condition->value
			? $if_true
			: $if_false;
	}

	/**
	 * Inspects the type of the argument, returning it as an unquoted string.
	 *
	 * @param Literals\Literal $obj The object to inspect
	 * @return Literals\SassString The type of object
	 */
	public static function type_of($obj)
	{
		Literals\Literal::assertType($obj, 'Literal');

		return new Literals\SassString($obj->typeOf);
	}

	/**
	 * Ensures the value is within the given range, clipping it if needed.
	 *
	 * @param float $value the value to test
	 * @param float $min the minimum value
	 * @param float $max the maximum value
	 * @return float the value clipped to the range
	 */
	public static function inRange($value, $min, $max)
	{
		return $value<$min
			? $min
			: ($value>$max? $max : $value);
	}
}
